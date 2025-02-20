const presets = [
  [
    '@babel/preset-env',
    {
      corejs: '3.40',
      useBuiltIns: 'usage',
      modules: 'auto',
      shippedProposals: true,
    },
  ],
];

const plugins = [
  ['@babel/plugin-transform-optional-chaining'],
  ['@babel/plugin-proposal-pipeline-operator', { proposal: 'minimal' }],
  ['@babel/plugin-transform-class-properties'],
];

module.exports = {
  presets,
  plugins,
};
