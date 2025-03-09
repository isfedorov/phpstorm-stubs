const core = require('@actions/core');
const github = require('@actions/github');
const { execSync } = require('child_process');
const fs = require('fs');

async function run() {
    try {
        const token = core.getInput('github-token', { required: true });
        const octokit = github.getOctokit(token);
        const context = github.context;

        // Initialize and update submodule with explicit path
        console.log('Initializing and updating submodule...');
        execSync('git submodule update --init --recursive meta/attributes/public');

        // Create a temporary directory and copy all files
        const tempDir = 'release-temp';
        console.log('Preparing release files...');
        execSync(`rm -rf ${tempDir}`);
        execSync(`mkdir -p ${tempDir}`);
        
        // Copy main repository files
        console.log('Copying main repository files...');
        execSync(`git archive HEAD | tar x -C ${tempDir}`);

        // Copy submodule files
        console.log('Copying submodule files...');
        execSync(`cd meta/attributes/public && git archive HEAD | tar x -C ../../../${tempDir}/meta/attributes/public`);

        // Create ZIP archive
        console.log('Creating ZIP archive...');
        execSync(`cd ${tempDir} && zip -r ../source-code.zip .`);

        // Create release
        const ref = context.ref;
        const tagName = ref.replace('refs/tags/', '');
        
        if (!ref.startsWith('refs/tags/')) {
            throw new Error('This action should be triggered by a tag push');
        }
        
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

        // Upload the ZIP file as Source code
        console.log('Uploading source code archive...');
        await octokit.rest.repos.uploadReleaseAsset({
            ...context.repo,
            release_id: release.data.id,
            name: 'Source code.zip',
            data: fs.readFileSync('source-code.zip')
        });

        // Clean up
        console.log('Cleaning up...');
        execSync(`rm -rf ${tempDir} source-code.zip`);

        console.log('Release created successfully!');
        core.setOutput("release-url", release.data.html_url);

    } catch (error) {
        console.error('Error details:', error);
        core.setFailed(error.message);
    }
}

run();