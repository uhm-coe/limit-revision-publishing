# Limit Revision Publishing

* WordPress Plugin: [https://wordpress.org/plugins/authorizer/](https://wordpress.org/plugins/authorizer/)
* Changelog: [https://github.com/uhm-coe/limit-revision-publishing/blob/master/readme.txt](https://github.com/uhm-coe/limit-revision-publishing/blob/master/readme.txt)

__Limit Revision Publishing__ restricts edits made by users without the `publish_posts` capability. Their edits will be saved as an unpublished revision, and the original post will remain published. The All Posts view in the WordPress Dashboard will contain a column indicating posts with unpublished revisions. Notification emails can be sent to a subset of users whenever a new revision has been submitted so users with elevated privileges can publish them. Finally, specific roles can be marked as limited, so any users with that role will be restricted from publishing revisions on all post types.

> Note: this plugin respects per-post-type capabilities, including `publish_posts`, `publish_pages`, and any custom post types with a custom `publish_{post_type}` capability.

## Installation
Install the plugin as normal from the WordPress plugin repository. Once activated, you will see a new Settings page at __Dashboard__ > __Settings__ > __Limit Revision Publishing__.

If your site has no custom roles, this plugin has no effect until you choose a role to restrict. If you have defined a custom role that has the `edit_posts` capability but not the `publish_posts` capability, then whenever a user with that role edits a published post, their changes are saved as an unpublished revision that needs to be approved by another user with the `publish_posts` capability.

> Note: by default, WordPress allows updating a published post by any user with the `edit_post` capability, even if they do not have the `publish_post` capability. This plugin changes that behavior.

## Configuration

On the Settings page at __Dashboard__ > __Settings__ > __Limit Revision Publishing__, you can choose to send customized __Email Notifications__ to specific users or roles whenever a limited user creates an unpublished revision. You can customize the email subject and body, and choose which users or roles receive the notifications.

![](images/screenshot-1.png?raw=true "Notification settings on the Limit Revision Publishing settings page.")

You may also choose to restrict existing roles regardless of their `publish_{post_type}` capabilities. Any roles you restrict in this way will not be able to publish any revisions to any existing post types.

![](images/screenshot-2.png?raw=true "Role settings on the Limit Revision Publishing settings page.")

## Example

In this example, we configure __Limit Revision Publishing__ to simply restrict any edits made by users with the _Author_ role, and send notifications to users with the _Administrator_ role.

To do this, simply add _Administrator_ to the __Roles to notify__ option, and add _Author_ to the __Roles to restrict__ option, as shown here:

![](images/screenshot-3.png?raw=true "Example settings page with Authors restricted and Administrators notified.")

Now, let's say we have an _Author_ attempting to edit a post called __Example Post__: they want to add the sentence, "This is my new content I am adding." This is what they see when editing (Note the _Submit for Review_ button):

![](images/screenshot-4.png?raw=true "Example author editing a Post.")

Once they save their edits, an email notification is sent to _Administrators_:

> __Subject__: Pending revision by author@example.com on Example Post

> A new revision has been submitted for review. Please approve or deny it here:<br>https://example.com/wp-admin/post.php?post=123&action=edit&classic-editor

If an _Administrator_ follows the link in the notification email, they will be taken the Edit Post page where they can see the pending changes, compare them to the current revision, and publish (or discard) the pending changes:

![](images/screenshot-5.png?raw=true "Example administrator viewing a pending revision.")

Alternatively, any user with publish capabilities can see Posts with pending changes when they view the __All Posts__ dashboard page:

![](images/screenshot-6.png?raw=true "Example administrator browsing All Posts with one pending revision.")

And if they click on the icon in the Pending Revision column, they are taken to the revision browser where they can approve the change:

![](images/screenshot-7.png?raw=true "Example administrator viewing a pending revision in the revision browser.")
