const core = require('@actions/core');
const github = require('@actions/github');
const { execSync } = require('child_process');
const fs = require('fs');
const path = require('path');

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
        console.log('Saving submodule files...');
        execSync('mkdir -p temp_submodule');
        execSync('cp -r meta/attributes/public/* temp_submodule/');

        // Filter only PHP files
        console.log('Filtering PHP files...');
        const tempDir = 'temp_submodule/';
        const phpFilesDir = 'filtered_submodule/';

        if (!fs.existsSync(phpFilesDir)) {
            fs.mkdirSync(phpFilesDir, { recursive: true });
        }

        const files = fs.readdirSync(tempDir);
        files.forEach(file => {
            const fullPath = path.join(tempDir, file);
            if (fs.lstatSync(fullPath).isFile() && file.endsWith('.php')) {
                fs.copyFileSync(fullPath, path.join(phpFilesDir, file));
            }
        });


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
        execSync('rm -rf temp_submodule');

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