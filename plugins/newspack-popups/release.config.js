// Auto-generated release config for multi-semantic-release.
// Mirrors the config from newspack-scripts/scripts/release.js.
module.exports = {
	branches: [
		'release',
		{ name: 'alpha', prerelease: true },
		{ name: 'hotfix/*', prerelease: '${name.replace(/\\\//g, "-")}' },
		{ name: 'epic/*', prerelease: '${name.replace(/\\\//g, "-")}' },
	],
	tagFormat: 'newspack-popups-v${version}',
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
				files: [ 'newspack-popups.php' ],
				callback: 'npm run release:archive',
			},
		],
		[
			'@semantic-release/github',
			{
				assets: [
					{
						path: './release/newspack-popups.zip',
						label: 'newspack-popups.zip',
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
				files: [ 'newspack-popups.php' ],
				callback: 'npm run release:archive',
			},
		],
		{
			path: '@semantic-release/git',
			assets: [
				'newspack-popups.php',
				'package.json',
				'CHANGELOG.md',
			],
			message: 'chore(release): ${nextRelease.version} [skip ci]\n\n${nextRelease.notes}',
		},
	],
};
