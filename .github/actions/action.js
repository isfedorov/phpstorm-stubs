const core = require('@actions/core');
const github = require('@actions/github');
const { execSync } = require('child_process');
const fs = require('fs'); // Import fs module
const path = require('path'); // Import path module

// Recursive function to find all PHP files
function findPhpFilesRecursively(dir, fileList = []) {
    const files = fs.readdirSync(dir);

    files.forEach(file => {
        const fullPath = path.join(dir, file);

        // Check if the file is a directory
        if (fs.lstatSync(fullPath).isDirectory()) {
            // If a directory, recursively fetch PHP files from it
            findPhpFilesRecursively(fullPath, fileList);
        } else if (file.endsWith('.php')) {
            // If a .php file, add to the list
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

        // Process submodule files
        console.log('Processing submodule and filtering PHP files recursively...');
        const submoduleDir = 'meta/attributes/public/';
        const filteredSubmoduleDir = 'filtered_submodule/';

        if (!fs.existsSync(filteredSubmoduleDir)) {
            fs.mkdirSync(filteredSubmoduleDir, { recursive: true });
        }

        // Find PHP files in the submodule directory
        const submodulePhpFiles = findPhpFilesRecursively(submoduleDir);

        // Copy PHP files from the submodule to the filtered directory
        submodulePhpFiles.forEach(file => {
            const relativePath = path.relative(submoduleDir, file);
            const destinationPath = path.join(filteredSubmoduleDir, relativePath);

            const destinationDir = path.dirname(destinationPath);
            if (!fs.existsSync(destinationDir)) {
                fs.mkdirSync(destinationDir, { recursive: true });
            }

            fs.copyFileSync(file, destinationPath);
        });

        console.log(`Found ${submodulePhpFiles.length} PHP files in the submodule.`);

        // Remove the submodule
        console.log('Removing submodule...');
        execSync('git submodule deinit -f meta/attributes/public');
        execSync('git rm -f meta/attributes/public');
        execSync('rm -rf .git/modules/meta/attributes/public');

        // Process main repository files
        console.log('Processing main repository and filtering PHP files recursively...');
        const repoDir = './'; // Root of the repository
        const filteredRepoDir = 'filtered_main_repo/';

        if (!fs.existsSync(filteredRepoDir)) {
            fs.mkdirSync(filteredRepoDir, { recursive: true });
        }

        // Find PHP files in the main repository (ignore some directories, if needed)
        const repoPhpFiles = findPhpFilesRecursively(repoDir).filter(file => {
            // Skip temporary or output directories (e.g., filtered output folders) to avoid infinite loops
            return !file.includes('filtered_main_repo') && !file.includes('filtered_submodule');
        });

        // Copy PHP files from the main repo to the filtered directory
        repoPhpFiles.forEach(file => {
            const relativePath = path.relative(repoDir, file);
            const destinationPath = path.join(filteredRepoDir, relativePath);

            const destinationDir = path.dirname(destinationPath);
            if (!fs.existsSync(destinationDir)) {
                fs.mkdirSync(destinationDir, { recursive: true });
            }

            fs.copyFileSync(file, destinationPath);
        });

        console.log(`Found ${repoPhpFiles.length} PHP files in the main repository.`);

        // Verify that we have PHP files from both sources
        if (repoPhpFiles.length === 0 && submodulePhpFiles.length === 0) {
            console.error('No PHP files found in either the main repository or the submodule.');
            core.setFailed('No PHP files to include in the release artifacts.');
            return;
        }

        // Combine filtered submodule and main repository into final directory (optional if needed for release)
        console.log('Combining PHP files from submodule and main repository...');
        execSync('mkdir -p release/');
        execSync(`cp -r ${filteredSubmoduleDir}/* release/ || true`); // Copy submodule files
        execSync(`cp -r ${filteredRepoDir}/* release/ || true`); // Copy main repo files

        // Clean up temporary directories
        console.log('Cleaning up temporary directories...');
        execSync(`rm -rf ${filteredSubmoduleDir}`);
        execSync(`rm -rf ${filteredRepoDir}`);

        // Add processed files to the repository and commit
        console.log('Committing filtered PHP files...');
        execSync('git add -f release/');
        execSync('git commit -m "Pack only PHP files from main repository and submodule for release"');

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