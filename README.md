![Development stage: alpha](https://img.shields.io/badge/development%20stage-preview-blue)
[![Software License](https://img.shields.io/badge/license-AGPL-brightgreen.svg)](LICENSE)

> [!NOTE]  
> If you're coming from the Nextcloud Conf 2024 lightning talk, you can find the [slides here](https://cloud.bitfire.at/s/WoPZaEnC9tmNxa6).

# Nextcloud extension for WebDAV-Push

`nc_ext_dav_push` is a [Nextcloud](https://github.com/nextcloud/server) extension to demonstrate [WebDAV-Push](https://github.com/bitfireAT/webdav-push/) support on calendars/address books.

It is the server part of our efforts to draft a WebDAV-Push standard and provide a working implementation (server + client) in order to demonstrate it.

**This extension is in a very early stage of development. It is for demonstration and testing purposes only. Don't use it on production systems!**

For instance, push subscriptions currently don't expire, can't be deleted by clients and won't be removed when they have become invalid. So the table will grow bigger and bigger and everything will become slow over time.

You can however install/enable the extension to test it and disable or remove it again at any time. When the extension is disabled, it doesn't influence your system.


## About WebDAV-Push

In proprietary environments, changes in events and contacts are nowadays usually pushed to other clients so that they can update their views almost in real-time.

WebDAV however (and in this context, especially CalDAV and CardDAV) doesn't currently support push notifications of clients when a collection has changed. So clients have to periodically ask the server for changes. This causes unnecessary delays and battery usage.

WebDAV-Push, which is currently in development, wants to solve this problem with an open protocol, too. See the [WebDAV-Push repository](https://github.com/bitfireAT/webdav-push/) for more information.


## Who is behind WebDAV-Push?

The current WebDAV-Push draft is provided by [@bitfireAT](https://github.com/bitfireAT).

This Nextcloud extension has been developed by [@JonathanTreffler](https://github.com/JonathanTreffler) for [@verdigado](https://github.com/verdigado), who are also interested in WebDAV-Push.


## Contact

If you have questions/suggestions or just want to show your interest about

- WebDAV-Push in general, see [WebDAV-Push: Contact](https://github.com/bitfireAT/webdav-push/#contact) for various options;
- for discussion about this Nextcloud extension specifically, use the [issues](https://github.com/bitfireAT/nc_ext_dav_push/issues).


# Installation instructions

## App Store
- Open App Store of your Nextcloud instance
- Search for "DAV Push"
- Click install button

For more details see the [apps management section of the nextcloud docs](https://docs.nextcloud.com/server/stable/admin_manual/apps_management.html)

## Latest development version
- Clone this repository into your apps directory (currently no build step is required, this may change in the future)
- Open App Store of your Nextcloud instance
- Search for "DAV Push"
- Click enable button

## Usage with DAVx⁵

When you have installed the Nextcloud extension, you need a client that supports WebDAV-Push to make use of it.

Currently, only DAVx⁵ (≥ 4.4.2) supports WebDAV-Push. To get it working:

1. Install and enable `nc_ext_dav_push` on your server (see above).
2. Install DAVx⁵, add your server. If you already have DAVx⁵ configured with your server, choose _Refresh collection list_.
3. DAVx⁵ should now show _Server advertises Push support_ in the details view for the calendars of this server.
4. Install a [UnifiedPush distributor](https://unifiedpush.org/users/distributors/) like [ntfy](https://ntfy.sh/).
5. Connect it with DAVx⁵ app settings / _UnifiedPush (experimental)_. Now the subscription should show up in ntfy (if you're using ntfy).
6. Activate sync for one or more calendars. In the details view, DAVx⁵ should show _Push support: Subscribed_ after a few seconds.
7. Whenever a new event is created or an existing event is updated by any client (for instance, in the Nextcloud Calendar), DAVx⁵ should receive a push notification and start a sync very soon (usually a few seconds).

See https://github.com/bitfireAT/davx5-ose/discussions/983 for screenshots.
