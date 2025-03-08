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

        // Create a ZIP archive with submodule files
        console.log('Creating archive with submodule files...');
        const archiveName = 'release-with-submodule.zip';
        execSync(`git archive --format=zip HEAD > ${archiveName}`);
        
        // Add submodule contents to the archive
        console.log('Adding submodule contents to archive...');
        execSync(`cd meta/attributes/public && git archive HEAD --prefix=meta/attributes/public/ --format=zip >> ../../${archiveName}`);

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

        // Upload the archive as the main source code
        console.log('Uploading archive to release...');
        await octokit.rest.repos.uploadReleaseAsset({
            ...context.repo,
            release_id: release.data.id,
            name: 'Source code (zip)',
            data: fs.readFileSync(archiveName)
        });

        // Clean up
        fs.unlinkSync(archiveName);

        console.log('Release created successfully!');
        core.setOutput("release-url", release.data.html_url);

    } catch (error) {
        console.error('Error details:', error);
        core.setFailed(error.message);
    }
}

run();