const core = require('@actions/core');
const github = require('@actions/github');
const { exec } = require('child_process');
const fs = require('fs');
const path = require('path');
const util = require('util');

const execAsync = util.promisify(exec);

async function readDirRecursively(dir) {
    const stack = [dir];
    const fileList = [];

    while (stack.length > 0) {
        const currentDir = stack.pop();
        const entries = await fs.promises.readdir(currentDir, { withFileTypes: true });

        for (const entry of entries) {
            const fullPath = path.join(currentDir, entry.name);
            if (entry.isDirectory()) {
                stack.push(fullPath);
            } else {
                fileList.push(fullPath);
            }
        }
    }

    return fileList;
}

async function configureGit() {
    const gitUserName = core.getInput('git-user-name') || 'GitHub Action';
    const gitUserEmail = core.getInput('git-user-email') || 'action@github.com';

    core.info('Configuring Git...');
    await execAsync(`git config --global user.name "${gitUserName}"`);
    await execAsync(`git config --global user.email "${gitUserEmail}"`);
}

async function manageSubmoduleFiles(tempDir, phpFilesDir) {
    core.info('Initializing and updating submodule...');
    await execAsync('git submodule update --init --recursive meta/attributes/public');

    core.info(`Creating directories: ${tempDir} and ${phpFilesDir}`);
    await execAsync(`mkdir -p ${tempDir}`);
    await execAsync(`mkdir -p ${phpFilesDir}`);

    core.info('Saving submodule files...');
    await execAsync(`cp -r meta/attributes/public/* ${tempDir}`);

    core.info(`Reading contents of ${tempDir} recursively...`);
    const allFiles = await readDirRecursively(tempDir);

    core.info('Filtering PHP files...');
    for (const filePath of allFiles) {
        if (filePath.endsWith('.php')) {
            const fileName = path.basename(filePath);
            const destPath = path.join(phpFilesDir, fileName);
            await fs.promises.copyFile(filePath, destPath);
        }
    }

    const phpFiles = await fs.promises.readdir(phpFilesDir);
    if (phpFiles.length === 0) {
        core.warning('No PHP files found during filtering.');
    } else {
        core.info(`PHP files successfully filtered and copied (${phpFiles.length} files).`);
    }

    core.info('Removing submodule...');
    await execAsync('git submodule deinit -f meta/attributes/public');
    await execAsync('git rm -f meta/attributes/public');
    await execAsync('rm -rf .git/modules/meta/attributes/public');

    core.info('Restoring filtered PHP files...');
    await execAsync(`mkdir -p meta/attributes/public`);
    await execAsync(`cp -r ${phpFilesDir}/* meta/attributes/public/`);
}

async function commitAndPushChanges(tagName) {
    const tempBranch = `release-${Date.now()}`;
    core.info(`Creating temporary branch ${tempBranch}...`);
    await execAsync(`git checkout -b ${tempBranch}`);

    core.info('Committing changes...');
    await execAsync('git add -f meta/attributes/public/');
    await execAsync('git commit -m "Convert submodule to regular files for release"');

    core.info('Updating and pushing tag...');
    await execAsync(`git tag -f ${tagName}`);
    await execAsync('git push origin --force --tags');
}

async function createGithubRelease(octokit, tagName, releaseName, context) {
    core.info(`Creating release ${releaseName} from tag ${tagName}...`);
    const release = await octokit.rest.repos.createRelease({
        ...context.repo,
        tag_name: tagName,
        name: releaseName,
        body: 'Automated release including submodule files',
        draft: false,
        prerelease: false
    });

    core.info('Release created successfully!');
    core.setOutput('release-url', release.data.html_url);
}

function getTagName(ref) {
    if (!ref.startsWith('refs/tags/')) {
        throw new Error('This action should be triggered by a tag push');
    }
    return ref.replace('refs/tags/', '');
}

async function run() {
    try {
        const token = core.getInput('github-token', { required: true });
        const octokit = github.getOctokit(token);

        const context = github.context;
        await configureGit();

        const tempDir = process.env.TEMP_DIR || 'temp_submodule';
        const phpFilesDir = process.env.PHP_FILES_DIR || 'filtered_submodule';

        try {
            await manageSubmoduleFiles(tempDir, phpFilesDir);
        } finally {
            core.info('Cleaning up temporary directories...');
            fs.rmSync(tempDir, { recursive: true, force: true });
            fs.rmSync(phpFilesDir, { recursive: true, force: true });
        }

        const tagName = getTagName(context.ref);
        const releaseName = `PhpStorm ${tagName.replace('v', '')}`;

        await commitAndPushChanges(tagName);
        await createGithubRelease(octokit, tagName, releaseName, context);
    } catch (error) {
        core.error(`Run failed: ${error.message}`);
        core.setFailed(error.message);
    }
}

// Top-level error handling
run().catch(error => {
    core.error(`Unhandled error: ${error.message}`);
    core.setFailed(error.message);
});