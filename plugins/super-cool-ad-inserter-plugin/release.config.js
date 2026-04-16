// Auto-generated release config for multi-semantic-release.
// Mirrors the config from newspack-scripts/scripts/release.js.
module.exports = {
	branches: [
		'release',
		{ name: 'alpha', prerelease: true },
		{ name: 'hotfix/*', prerelease: '${name.replace(/\\\//g, "-")}' },
		{ name: 'epic/*', prerelease: '${name.replace(/\\\//g, "-")}' },
	],
	tagFormat: 'super-cool-ad-inserter-plugin-v${version}',
	plugins: [
		'@semantic-release/commit-analyzer',
		'@semantic-release/release-notes-generator',
		[
			'@semantic-release/npm',
			{ npmPublish: false },
		],
		[
			'semantic-release-version-bump',
			{
				files: [ 'super-cool-ad-inserter-plugin.php' ],
				callback: 'npm run release:archive',
			},
		],
		[
			'@semantic-release/github',
			{
				assets: [
					{
						path: './release/super-cool-ad-inserter-plugin.zip',
						label: 'super-cool-ad-inserter-plugin.zip',
					},
				],
			},
		],
	],
	prepare: [
		'@semantic-release/changelog',
		'@semantic-release/npm',
		[
			'semantic-release-version-bump',
			{
				files: [ 'super-cool-ad-inserter-plugin.php' ],
				callback: 'npm run release:archive',
			},
		],
		{
			path: '@semantic-release/git',
			assets: [
				'super-cool-ad-inserter-plugin.php',
				'package.json',
				'CHANGELOG.md',
			],
			message: 'chore(release): ${nextRelease.version} [skip ci]\n\n${nextRelease.notes}',
		},
	],
};
