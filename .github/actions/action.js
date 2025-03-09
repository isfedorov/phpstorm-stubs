const core = require('@actions/core');
const github = require('@actions/github');
const { execSync } = require('child_process');
const fs = require('fs');
const path = require('path');

/**
 * Recursively reads a directory and returns all files within it.
 * @param {string} dir - The directory to start reading from.
 * @param {Array<string>} [fileList] - An array used during recursion to collect file paths.
 * @returns {Array<string>} - A flat list of all file paths.
 */
function readDirRecursively(dir, fileList = []) {
    const entries = fs.readdirSync(dir); // Get all files and subdirectories in the current directory

    entries.forEach(entry => {
        const fullPath = path.join(dir, entry); // Get full path of the entry
        if (fs.lstatSync(fullPath).isDirectory()) {
            // If the entry is a directory, recursively read its contents
            readDirRecursively(fullPath, fileList);
        } else {
            // If it's a file, add it to the file list
            fileList.push(fullPath);
        }
    });

    return fileList; // Return the accumulated list of file paths
}


async function run() {
    try {
        const token = core.getInput('github-token', { required: true });
        const octokit = github.getOctokit(token);
        const context = github.context;

        // Configure git
        console.log('Configuring git...');
        execSync('git config --global user.name "GitHub Action"');
        execSync('git config --global user.email "action@github.com"');

        // Initialize and update submodule with explicit path
        console.log('Initializing and updating submodule...');
        execSync('git submodule update --init --recursive meta/attributes/public');

        // Create a temporary branch
        const tempBranch = `release-${Date.now()}`;
        console.log(`Creating temporary branch ${tempBranch}...`);
        execSync(`git checkout -b ${tempBranch}`);

        // Save submodule files to a temporary location
        const tempDir = 'temp_submodule';
        const phpFilesDir = 'filtered_submodule';
        console.log('Saving submodule files...');
        execSync(`mkdir -p ${tempDir}`);
        execSync(`cp -r meta/attributes/public/* ${tempDir}`);

        console.log(`Reading contents of ${tempDir} recursively...`);
        const allFiles = readDirRecursively(tempDir);

        // Make sure filtered_submodule exists
        if (!fs.existsSync(phpFilesDir)) {
            fs.mkdirSync(phpFilesDir, { recursive: true });
        }

        // Filter only `.php` files and copy them to the `filtered_submodule` directory
        console.log('Filtering PHP files...');
        allFiles.forEach(filePath => {
            if (filePath.endsWith('.php')) {
                const fileName = path.basename(filePath); // Extract file name
                const destPath = path.join(phpFilesDir, fileName); // Destination path
                fs.copyFileSync(filePath, destPath); // Copy the file
            }
        });

        if (fs.readdirSync(phpFilesDir).length === 0) {
            console.log('No PHP files found during filtering.');
        } else {
            console.log('PHP files successfully filtered and copied.');
        }



        // Remove submodule
        console.log('Removing submodule...');
        execSync('git submodule deinit -f meta/attributes/public');
        execSync('git rm -f meta/attributes/public');
        execSync('rm -rf .git/modules/meta/attributes/public');

        // Create the directory and copy filtered PHP files back
        console.log('Restoring filtered PHP files...');
        execSync('mkdir -p meta/attributes/public');
        execSync(`cp -r ${phpFilesDir}/* meta/attributes/public/`);
        execSync(`rm -rf ${phpFilesDir}`);
        execSync(`rm -rf ${tempDir}`);

        // Add and commit the changes
        console.log('Committing changes...');
        execSync('git add -f meta/attributes/public/');
        execSync('git commit -m "Convert submodule to regular files for release"');

        // Get the tag name
        const ref = context.ref;
        const tagName = ref.replace('refs/tags/', '');
        
        if (!ref.startsWith('refs/tags/')) {
            throw new Error('This action should be triggered by a tag push');
        }

        // Update the tag
        console.log('Updating tag...');
        execSync(`git tag -f ${tagName}`);
        execSync('git push origin --force --tags');

        // Create the release
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