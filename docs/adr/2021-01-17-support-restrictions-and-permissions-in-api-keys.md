# Support restrictions and permissions in API keys

* Status: Accepted
* Date: 2021-01-17

## Context and problem statement

Historically, every API key generated for Shlink granted you access to all existing resources.

The intention is to be able to apply some form of restriction to API keys, so that only a subset of "resources" can be accessed with it, naming:

* Allowing interactions only with short URLs and related resources, that have been created with the same API key.
* Allowing interactions only with short URLs and related resources, that have been attached to a specific domain.

The intention is to implement a system that allows adding to API keys as many of these restrictions as wanted.

Supporting more restrictions in the future is also desirable.

## Considered option

* Using an ACL/RBAC library, and checking roles in a middleware.
* Using a service that, provided an API key, tells if certain resource is reachable while it also allows building queries dynamically.
* Using some library implementing the specification pattern, to dynamically build queries transparently for outer layers.

## Decision outcome

The main difficulty on implementing this is that the entity conditioning the behavior (the API key) comes in the request in some form, but it can potentially affect database queries performed in the persistence layer.

Because of this, it has to traverse all the application layers from top to bottom, in most of the cases.

This motivated selecting the third option, as we can propagate the API key and delay its handling to the last step, without changing the behavior of the rest of the layers that much (except in some individual use cases).

The domain term used to refer these "restrictions" is finally **roles**.

It can be combined in the future with an ACL/RBAC library, if we want to restrict access to certain resources, but it didn't fulfil the initial requirements.

## Pros and Cons of the Options

### An ACL/RBAC library

* Good, because there are many good libraries out there.
* Bad, because when you need to filter resources lists this kind of libraries doesn't really work.

### A service with the logic

* Bad, because it would need to be used in many layers of the application, mixing unrelated concerns.

### A library implementing the specification pattern

* Good, because allows centralizing the generation of dynamic specs by the entity itself, that are later translated automatically into database queries.
