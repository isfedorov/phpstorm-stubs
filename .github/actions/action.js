const core = require('@actions/core');
const github = require('@actions/github');
const { execSync } = require('child_process');

async function run() {
try {
    // Get the GitHub token from inputs
    const token = core.getInput('github-token', { required: true });
    const octokit = github.getOctokit(token);
    const context = github.context;

    // Initialize and update submodule
    console.log('Initializing and updating submodule...');
    execSync('git submodule init');
    execSync('git submodule update');

     // Create a temporary directory for organizing files
     const tempDir = 'release-temp';
     const targetDir = path.join(tempDir, 'meta/attributes/public');
     fs.mkdirSync(targetDir, { recursive: true });

     // Copy PHP files from submodule to temp directory
     console.log('Checking PHP files in submodule...');
        const submodulePath = 'meta/attributes/public';
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

        console.log('Release created successfully!');
        core.setOutput("release-url", release.data.html_url);

} catch (error) {
    core.setFailed(error.message);
}
}

run();