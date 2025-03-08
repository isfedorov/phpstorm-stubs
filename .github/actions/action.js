const core = require('@actions/core');
const github = require('@actions/github');
const { execSync } = require('child_process');

try {
    // Get the GitHub token from inputs
    const token = core.getInput('github-token', { required: true });
    const octokit = github.getOctokit(token);
    const context = github.context;

    // Initialize and update submodule
    console.log('Initializing and updating submodule...');
    execSync('git submodule init');
    execSync('git submodule update');

    // Navigate to submodule and get files
    console.log('Collecting PHP files from submodule...');
    const submodulePath = 'meta/attributes/public';
    const files = execSync(`cd ${submodulePath} && find . -name "*.php"`)
        .toString()
        .trim()
        .split('\n')
        .map(file => file.replace('./', '')); // Remove leading './'

    if (files.length === 0 || (files.length === 1 && files[0] === '')) {
        console.log('No PHP files found in submodule');
        return;
    }

    // Create a new release
    const ref = context.ref; // refs/tags/v2024.3
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

    // Upload submodule files to the release
    for (const file of files) {
        const filePath = `${submodulePath}/${file}`;
        await octokit.rest.repos.uploadReleaseAsset({
            ...context.repo,
            release_id: release.data.id,
            name: file,
            data: require('fs').readFileSync(filePath)
        });
    }

    console.log('Release created successfully!');
    core.setOutput("release-url", release.data.html_url);

} catch (error) {
    core.setFailed(error.message);
}