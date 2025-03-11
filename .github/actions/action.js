const core = require('@actions/core');
const github = require('@actions/github');
const {exec} = require('child_process');
const fs = require('fs');
const path = require('path');
const util = require('util');
const execAsync = util.promisify(exec);

async function run() {
    try {
        const token = core.getInput('github-token', { required: true });
        const octokit = github.getOctokit(token);
        const context = github.context;
        const tagName = await getTagName(context.ref);
        const releaseName = `PhpStorm ${tagName.replace('v', '')}`;
        const tempDir = process.env.TEMP_DIR || 'temp_submodule';
        const phpFilesDir = process.env.PHP_FILES_DIR || 'filtered_submodule';

        await configureGit();

        try {
            await createTemporaryBranch();
            await manageSubmoduleFiles(tempDir, phpFilesDir);
        } finally {
            core.info('Cleaning up temporary directories...');
            fs.rmSync(tempDir, { recursive: true, force: true });
            fs.rmSync(phpFilesDir, { recursive: true, force: true });
        }

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

/**
 * @param {string} dir - The directory to start reading from.
 * @param {Array<string>} [fileList] - An array used during recursion to collect file paths.
 * @returns {Array<string>} - A flat list of all file paths.
 */
async function readDirRecursively(dir, fileList = []) {
    const entries = await fs.promises.readdir(dir, { withFileTypes: true });

    await Promise.all(
        entries.map(async entry => {
            const fullPath = path.join(dir, entry.name);
            if (entry.isDirectory()) {
                return readDirRecursively(fullPath, fileList);
            } else {
                fileList.push(fullPath);
            }
        })
    );

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
    console.log('Initializing and updating submodule...');
    await execAsync('git submodule update --init --recursive');

    const tempBranch = `release-${Date.now()}`;
    console.log(`Creating temporary branch ${tempBranch}...`);
    await execAsync(`git checkout -b ${tempBranch}`);

    console.log('Saving submodule files...');
    fs.mkdirSync(tempDir, {recursive: true});
    fs.mkdirSync(phpFilesDir, {recursive: true});
    await execAsync(`cp -r meta/attributes/public/* ${tempDir}`);


    core.info(`Reading contents of ${tempDir} recursively...`);
    core.info('Reading files recursively...');
    const allFiles = await readDirRecursively(tempDir);
    core.info(`Files read: ${allFiles.length}`);
    allFiles.forEach(filePath => {
        core.info(`Processing file: ${filePath}`);
    });


    console.log('Filtering PHP files...');
    allFiles.forEach(filePath => {
        if (filePath.endsWith('.php')) {
            const fileName = path.basename(filePath);
            const destPath = path.join(phpFilesDir, fileName);
            fs.copyFileSync(filePath, destPath);
        }
    });

    if (fs.readdirSync(phpFilesDir).length === 0) {
        console.log('No PHP files found during filtering.');
    } else {
        console.log('PHP files successfully filtered and copied.');
    }

    console.log('Removing submodule...');
    await execAsync('git submodule deinit -f -- meta/attributes/public');
    await execAsync('git rm -f meta/attributes/public');
    await execAsync('rm -rf .git/modules/meta/attributes/public');

    console.log('Restoring filtered PHP files...');
    await execAsync('mkdir -p meta/attributes/public');
    await execAsync(`cp -r ${phpFilesDir}/* meta/attributes/public/`);
    await execAsync(`rm -rf ${phpFilesDir}`);
    await execAsync(`rm -rf ${tempDir}`);
}

async function createTemporaryBranch() {
    const tempBranch = `release-${Date.now()}`;
    core.info(`Creating temporary branch ${tempBranch}...`);
    await execAsync(`git checkout -b ${tempBranch}`);
}

async function commitAndPushChanges(tagName) {
    console.log('Committing changes...');
    await execAsync('git add -f meta/attributes/public/');
    await execAsync('git commit -m "Convert submodule to regular files for release"');

    core.info('Updating and pushing tag...');
    await execAsync(`git tag -f ${tagName}`);
    await execAsync('git push origin --force --tags');
}

async function getTagName(ref) {
    if (!ref.startsWith('refs/tags/')) {
        throw new Error('This action should be triggered by a tag push');
    }
    return ref.replace('refs/tags/', '');
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