# Build `latest` docker image only for actual releases

* Status: Accepted
* Date: 2023-07-09

## Context and problem statement

Historically, this project has re-tagged the `latest´ docker image every time a PR was merged into default branch.

The reason was to be able to:

* Periodically test the docker building and publishing process.
* Provide "partial" images for quick testing of new "un-released" features.

However, this was considered non-stable, and not recommended to use in production. Instead, a convenient `stable` tag was provided, which was re-tagged for every new non-beta/non-alpha release.

The approach described above for `latest` has some problems, though:

* Many people ignore the recommendation of not using it in production. There have even been reports of bugs on things which were, technically speaking, not yet released.
* Since it is not always built for an actual new project version, the project itself cannot inform about anything other than `latest`, which can quickly become a lie if you don't update your local version.

## Considered options

* Try to provide a pseudo-version when `latest` is built. Something like `<prev_version>-<commit_hash>.
* Change how `latest` is published, and start tagging it only for actual new version releases.
* Same as the above, but exclude alpha/beta versions, deprecating `stable` tag.

## Decision outcome

Since testing un-released features has never been needed, it is probably a not-very useful thing to have.

Periodically testing the build and publish process can also be moved somewhere else, like a testing "hidden" account.

Also, having `stable` with non-alpha/non-beta releases seems sensible, so the decision is to "Change how `latest` is published, and start tagging it only for actual new version releases".

## Pros and Cons of the Options

### Try to provide a pseudo-version when `latest` is built.

* Good: because we keep publishing process intact, from a user point of view.
* Bad: because it requires adding some non-trivial logic to the image building, which needs to find out what was the latest stable release.

### Make `latest` hold latest published version, including unstable releases.

* Good: because it provides a way for users to test bleeding-edge features, with less risk than relying on the very last content from default branch.
* Good: because it allows for `stable` to be used together with `latest`.
* Bad: because partial features cannot be tested without publishing an alpha or beta version.

### Make `latest` hold latest published version, excluding unstable releases.

* Bad: because there's no longer a way to test bleeding-edge features, other than installing that specific version.
* Bad: because it drives `stable` useless, which means it needs to be deprecated, documented, and eventually removed.
