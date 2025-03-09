const core = require('@actions/core');
const github = require('@actions/github');
const { execSync } = require('child_process');

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

        // Get the current commit SHA
        const currentSha = execSync('git rev-parse HEAD').toString().trim();
        console.log(`Current commit: ${currentSha}`);

        // Create a temporary branch from the current state
        const tempBranch = `release-${Date.now()}`;
        console.log(`Creating temporary branch ${tempBranch}...`);
        execSync(`git checkout -b ${tempBranch}`);

        // Remove the submodule entry but keep its files
        console.log('Preparing submodule files...');
        execSync('git rm --cached meta/attributes/public');
        execSync('rm -rf .git/modules/meta/attributes/public');
        execSync('git add meta/attributes/public/');

        // Commit the changes
        console.log('Committing changes...');
        execSync('git commit -m "Include submodule files for release"');

        // Get the tag name and create release
        const ref = context.ref;
        const tagName = ref.replace('refs/tags/', '');
        
        if (!ref.startsWith('refs/tags/')) {
            throw new Error('This action should be triggered by a tag push');
        }

        // Force update the tag to our new commit
        const newSha = execSync('git rev-parse HEAD').toString().trim();
        console.log(`New commit: ${newSha}`);
        execSync(`git tag -f ${tagName} ${newSha}`);
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