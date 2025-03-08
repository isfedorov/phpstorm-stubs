const core = require('@actions/core');
const github = require('@actions/github');
const { execSync } = require('child_process');

async function run() {
    try {
        const token = core.getInput('github-token', { required: true });
        const octokit = github.getOctokit(token);
        const context = github.context;

        // Initialize and update submodule with explicit path
        console.log('Initializing and updating submodule...');
        execSync('git submodule update --init --recursive meta/attributes/public');

        // Verify PHP files exist in submodule
        console.log('Checking PHP files in submodule...');
        const submodulePath = 'meta/attributes/public';
        
        try {
            const files = execSync(`cd ${submodulePath} && find . -name "*.php"`)
                .toString()
                .trim()
                .split('\n')
                .filter(file => file !== '')
                .map(file => file.replace('./', ''));

            if (files.length === 0) {
                console.log('No PHP files found in submodule');
                return;
            }

            console.log(`Found ${files.length} PHP files in submodule`);

        } catch (error) {
            console.error(`Error accessing submodule: ${error.message}`);
            throw new Error('Failed to access submodule directory');
        }

        // Create release
        const ref = context.ref;
        const tagName = ref.replace('refs/tags/', '');
        
        if (!ref.startsWith('refs/tags/')) {
            throw new Error('This action should be triggered by a tag push');
        }
        
        const releaseName = `PhpStorm ${tagName.replace('v', '')}`;
        console.log(`Creating release ${releaseName} from tag ${tagName}...`);

        // Get the current commit SHA
        const sha = execSync('git rev-parse HEAD').toString().trim();
        console.log(`Creating release from commit: ${sha}`);

        const release = await octokit.rest.repos.createRelease({
            ...context.repo,
            tag_name: tagName,
            name: releaseName,
            body: 'Automated release including submodule files',
            draft: false,
            prerelease: false,
            target_commitish: sha
        });

        console.log('Release created successfully!');
        core.setOutput("release-url", release.data.html_url);

    } catch (error) {
        core.setFailed(error.message);
    }
}

run();