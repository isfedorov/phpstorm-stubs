const core = require('@actions/core');
const github = require('@actions/github');
const { execSync } = require('child_process');
const fs = require('fs');
const path = require('path');

/**
 * @param {string} dir - The directory to start reading from.
 * @param {Array<string>} [fileList] - An array used during recursion to collect file paths.
 * @returns {Array<string>} - A flat list of all file paths.
 */
function readDirRecursively(dir, fileList = []) {
    const entries = fs.readdirSync(dir);

    entries.forEach(entry => {
        const fullPath = path.join(dir, entry);
        if (fs.lstatSync(fullPath).isDirectory()) {
            readDirRecursively(fullPath, fileList);
        } else {
            fileList.push(fullPath);
        }
    });

    return fileList;
}


async function run() {
    try {
        const token = core.getInput('github-token', { required: true });
        const octokit = github.getOctokit(token);
        const context = github.context;

        console.log('Configuring git...');
        execSync('git config --global user.name "GitHub Action"');
        execSync('git config --global user.email "action@github.com"');

        console.log('Initializing and updating submodule...');
        execSync('git submodule update --init --recursive meta/attributes/public');

        const tempBranch = `release-${Date.now()}`;
        console.log(`Creating temporary branch ${tempBranch}...`);
        execSync(`git checkout -b ${tempBranch}`);

        const tempDir = 'temp_submodule';
        const phpFilesDir = 'filtered_submodule';
        console.log('Saving submodule files...');
        execSync(`mkdir -p ${tempDir}`);
        execSync(`cp -r meta/attributes/public/* ${tempDir}`);

        console.log(`Reading contents of ${tempDir} recursively...`);
        const allFiles = readDirRecursively(tempDir);

        if (!fs.existsSync(phpFilesDir)) {
            fs.mkdirSync(phpFilesDir, { recursive: true });
        }

        console.log('Filtering PHP files...');
        allFiles.forEach(filePath => {
            if (filePath.endsWith('.php')) {
                const fileName = path.basename(filePath);
                const destPath = path.join(phpFilesDir, fileName);
                fs.copyFileSync(filePath, destPath);
            }
        });

        if (fs.readdirSync(phpFilesDir).length === 0) {
            console.log('No PHP files found during filtering.');
        } else {
            console.log('PHP files successfully filtered and copied.');
        }

        console.log('Removing submodule...');
        execSync('git submodule deinit -f meta/attributes/public');
        execSync('git rm -f meta/attributes/public');
        execSync('rm -rf .git/modules/meta/attributes/public');

        console.log('Restoring filtered PHP files...');
        execSync('mkdir -p meta/attributes/public');
        execSync(`cp -r ${phpFilesDir}/* meta/attributes/public/`);
        execSync(`rm -rf ${phpFilesDir}`);
        execSync(`rm -rf ${tempDir}`);

        console.log('Committing changes...');
        execSync('git add -f meta/attributes/public/');
        execSync('git commit -m "Convert submodule to regular files for release"');

        const ref = context.ref;
        const tagName = ref.replace('refs/tags/', '');
        
        if (!ref.startsWith('refs/tags/')) {
            throw new Error('This action should be triggered by a tag push');
        }

        console.log('Updating tag...');
        execSync(`git tag -f ${tagName}`);
        execSync('git push origin --force --tags');

        const releaseName = `PhpStorm ${tagName.replace('v', '')}`;
        console.log(`Creating release ${releaseName} from tag ${tagName}...`);

        const release = await octokit.rest.repos.createRelease({
            ...context.repo,
            tag_name: tagName,
            name: releaseName,
            body: 'Automated release including submodule files',
            draft: false,
            prerelease: false
        });

        console.log('Release created successfully!');
        core.setOutput("release-url", release.data.html_url);

    } catch (error) {
        console.error('Error details:', error);
        core.setFailed(error.message);
    }
}

run();