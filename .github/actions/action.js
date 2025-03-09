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

        // Create a temporary branch
        const tempBranch = `release-${Date.now()}`;
        console.log(`Creating temporary branch ${tempBranch}...`);
        execSync(`git checkout -b ${tempBranch}`);

        // Save PHP files from submodule to a temporary location
        console.log('Saving PHP files from submodule...');
        execSync('mkdir -p temp_submodule');
        execSync('cd meta/attributes/public && find . -name "*.php" -type f -exec cp --parents {} ../../temp_submodule/ \\;');

        // Remove submodule
        console.log('Removing submodule...');
        execSync('git submodule deinit -f meta/attributes/public');
        execSync('git rm -f meta/attributes/public');
        execSync('rm -rf .git/modules/meta/attributes/public');

        // Create the directory and copy PHP files back
        console.log('Restoring PHP files from submodule...');
        execSync('mkdir -p meta/attributes/public');
        execSync('cp -r temp_submodule/* meta/attributes/public/ 2>/dev/null || true');
        execSync('rm -rf temp_submodule');

        // Add and commit the changes
        console.log('Committing changes...');
        execSync('git add -f meta/attributes/public/');
        execSync('git commit -m "Add PHP files from submodule for release"');

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
            body: 'Automated release including PHP files from submodule',
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