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

        // Create a temporary directory for the release
        const tempDir = 'release-temp';
        console.log('Creating temporary directory...');
        execSync(`rm -rf ${tempDir}`); // Clean up any existing temp directory
        execSync(`mkdir -p ${tempDir}`);

        // Create a file list and copy files
        console.log('Copying repository files...');
        execSync(`git ls-files > files.txt`);
        execSync(`rsync -R --files-from=files.txt . ${tempDir}/`);

        // Copy submodule files
        console.log('Copying submodule files...');
        execSync(`cd meta/attributes/public && git ls-files > ../../../submodule-files.txt`);
        execSync(`cd meta/attributes/public && rsync -R --files-from=../../submodule-files.txt . ../../${tempDir}/meta/attributes/public/`);

        // Create ZIP archive
        const archiveName = 'release-with-submodule.zip';
        console.log('Creating ZIP archive...');
        execSync(`cd ${tempDir} && zip -r ../${archiveName} .`);

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
            prerelease: false,
            generate_release_notes: true
        });

        // Upload the archive
        console.log('Uploading archive to release...');
        await octokit.rest.repos.uploadReleaseAsset({
            ...context.repo,
            release_id: release.data.id,
            name: 'Source code (zip)',
            data: fs.readFileSync(archiveName)
        });

        // Clean up
        console.log('Cleaning up...');
        execSync(`rm -rf ${tempDir} ${archiveName} files.txt submodule-files.txt`);

        console.log('Release created successfully!');
        core.setOutput("release-url", release.data.html_url);

    } catch (error) {
        console.error('Error details:', error);
        core.setFailed(error.message);
    }
}

run();