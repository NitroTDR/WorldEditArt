# Changelog
<!-- Uses format from https://github.com/olivierlacan/keep-a-changelog/blob/master/CHANGELOG.md -->
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/en/1.0.0/).

The plugin version comprises four numbers:
- Edition number: The number of times this project has been rewritten :sob: (This is epsilon, the 5<sup>th</sup> Greek alphabet, and I have rewritten WorldEditArt 4 times!)
- Major version: Bumped every time WorldEditArt's API has backward-incompatible changes
- Minor version: Bumped every time WorldEditArt's API has additions
- Patch version: Bumped every time WorldEditArt is released without API changes

## [Unreleased] (Epsilon 4.1.0.0)
### Added
- Basic player sessions
- Construction zones
- Bookmarks and `//at`
- Selections
  - //sels and //desel
  - Wands semi-internal API (the `AbstractFieldDefinitionWand` class must not be implemented by other plugins due to its usage of libgeom classes in parameter type hint)
  - Cuboid selection (commands and wands)
  - Cylinder selection (commands and wands)
    - Except:
      - `rightCircum` and `frontCircum` wands, as in ProjectProposal, have not been implemented yet.
      - `//cyl normalize false` (`false` for `preserveLength`) is not properly implemented, and temporarily redirects to `//cyl normalize true` due to technical difficulties that can be overcome _had I tried harder learning vector mathematics_.
    - **UNDOCUMENTED**

[Unreleased]: https://github.com/LegendOfMCPE/WorldEditArt/compare/delta/v3.0...HEAD