# Make Me Static - Plugin for Wordpress

Welcome to the Make Me Static Plugin for Wordpress. This software aims to create and 
maintain a static copy of your Wordpress website. The plugin provides a customised
sitemap and change tracking, then connects to the MMS service which does all the
heavy lifting.

As the site is scanned "from the outside", the process is not in a position to expose
any information that wasn't already publically visible.

Validation routines inside the plugin ensure that only site administrators can use the
software and hence kick of scans. Every time the dashboard is loaded and connects to
the MMS service, the service will run a call-back to the site's URL to verify the 
session is valid and was initiated by an administrator.

... lots more documentation to come (!)

### Security

The connection between the Plugin and MMS runs over secure websockets with authentication
provided by public / private key authentication which is negotiated on first contact. The
only security sensitive data part is "user_id" which is a anonymous identifier that uniquely
idenfies the client.

MMS validates connections against Wordpress using a public "host_id" token that is generated
as a part of the key exchange process. To accept a connections the "host_id" must match
a verified host_id written to Wordpress metadata by an authenticated administrator with a
currently active session.

All connections should be outgoing to the MMS platform so any issues should not affect the
integrity of the Wordpress instance. MMS does has no ability to modify the Wordpress instance.