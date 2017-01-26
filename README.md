# Z-plugin for usage with git

This plugin implements `vcs` commands which are used by other plugins to access
versioning information of the current project.

Mostly, the tasks and commands within this plugin are used by other plugins to
do versioning-related commands, such as:

* Showing the current version deployed on a remote (`env:version` from the
  `env` plugin)
* Showing a diff between remote version and current (`env:diff` from the `env`
  plugin)
* Creating a build of a specific version (`build` from the `build` plugin, used
  by the `deploy` plugin)

Some of the convenience commands that are available in this plugin are
implemented the same way as in the `svn` plugin, though you might not use them
as often when you're used to git.

# Maintainer(s)
* Philip Bergman <philip@zicht.nl>
