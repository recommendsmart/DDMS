# Flow

**This module automates your workflow on content.**

https://www.drupal.org/project/flow

## 0. Contents

- 1. Introduction
- 2. Requirements
- 3. Installation
- 4. Usage
- 5. Complementary projects
- 6. Maintainers
- 7. Support and contribute

## 1. Introduction

The Flow module assists you on automating workflow tasks related to content.
It does so by allowing you to setup tasks, that are being automatically applied
when content is about to be created, saved or deleted.

This module works on top of your data in the system: Every task and
subject always relates to a concrete data type, that is being defined by the
entity type and a bundle (for example node type "article").

## 2. Requirements

This module does not require anything outside of Drupal core.

## 3. Installation

Install the module as you would normally install a contributed
Drupal module. Visit https://www.drupal.org/node/1897420 for further
information.

## 4. Usage

For a first time usage and to manage Flow configuration through the user
interface, you need to install the included sub-module "Flow UI". Once that
is installed, you have a new tab in the field UI, i.e. everywhere a
"Manage fields" tab exists for managing the fields of a certain content entity
type, a further tab "Manage flow" appears.

After installation, you should take a look at the permissions page at
/admin/people/permissions and make sure whether the configured permissions are
properly set.

The "Manage flow" tab consists by default of 3 sub-tabs: "Create", "Save" and
"Delete" whereas the "Save" tab would be the default one opened when accessing
"Manage flow". The three tabs define a "task mode", which can be described as
follows:
- "Create" is the task mode that is being applied when a new instance of content
  is being created. This instantiation doesn't mean that it is being saved
  ("inserted"), instead this is always happening when a new object instance is
  being created on runtime. This task mode is suitable for prefilling default
  values of the related content, also known as "prepopulating".
- "Save" is the task mode that is being applied right before content is being
  persisted (meaning both first-time inserted and updated).
- "Delete" is the task mode that is being applied right before content is being
  deleted.

On every task mode, you can create a task that always operates on a certain
subject. A subject is always a concrete data type, like an article, blog post
or a "Tags" taxonomy term.

## 5. Complementary projects

This module contains additional sub-modules, all optional:
- **Flow UI**: A user interface for Flow. You most likely want to install this
  module to setup Flow configurations through the web user interface.
- **Actions for Flow**: Makes actions available for Flow configurations.
- **Flow Context**: Makes Flow-related content available as contextual Tokens.
  Requires the Context Stack module (drupal/context_stack).

When the Select2 (https://www.drupal.org/project/select2) module is installed,
the user interface uses that widget to improve selection on the vast number of
available tasks and subjects.

When working with Tokens, the contrib Token module
(https://www.drupal.org/project/token) is recommended to be installed.
That module provides a Token browser for a convenient way to browse through
available Tokens.

This module is a tool for workflow automation, by configuring tasks that are to
be executed automatically on content operations. There might be use cases
for automation, that go beyond the scope of content operations, and exceed the
level of complexity this module is not able to cover. For such use cases, you
might need the scale of a rules-engine. The following modules could be
considered when you need a rules-engine:
* Rules (https://www.drupal.org/project/rules)
* ECA: Event - Condition - Action (https://www.drupal.org/project/eca)
* Business Rules (https://www.drupal.org/project/business_rules)
This list is not kept up-to date. Inform yourself about current solutions
for having a rules-engine in Drupal.

## 6. Maintainers

* Maximilian Haupt (mxh) - https://www.drupal.org/u/mxh

## 7. Support and contribute

To submit bug reports and feature suggestions, or to track changes visit:
https://www.drupal.org/project/issues/flow

You can also use this issue queue for contributing, either by submitting ideas,
or new features and mostly welcome - patches and tests.
