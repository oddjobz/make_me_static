<?xml version="1.0" encoding="UTF-8"?>
	<xsl:stylesheet version="2.0"
		xmlns:html="http://www.w3.org/TR/REC-html40"
		xmlns:image="http://www.google.com/schemas/sitemap-image/1.1"
		xmlns:sitemap="http://www.sitemaps.org/schemas/sitemap/0.9"
		xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
	<xsl:output method="html" version="1.0" encoding="UTF-8" indent="yes"/>
	<xsl:template match="/">
		<html xmlns="http://www.w3.org/1999/xhtml">
		<head>
			<title>MMS Sitemap</title>
			<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
			<style type="text/css">
				body {
					font-family: BlinkMacSystemFont,-apple-system,Segoe UI,Roboto,Oxygen,Ubuntu,Cantarell,Fira Sans,Droid Sans,Helvetica Neue,Helvetica,Arial,sans-serif;
					font-size: 1.2em;
					padding: 2em;
				}
				.explain {
					background-color: #99ccff;
					padding: 0.5em;
					border-radius: 8px;
				}
				.generated {
					background-color: #ffffcc;
					padding: 0.5em;
					border-radius: 8px;
					margin-top:1em;
				}
				#content, #sitemap {
					width: 1000px;
				}
				#sitemap {
					background-color: #ccffcc;
					padding: 0.5em;
					border-radius: 8px;
				}
				#sitemap th {
					text-align: left;
					min-width: 15rem;
				}
				#sitemap tr {
				}
				a {
					color: #000;
					text-decoration: none;
				}
				a:visited {
				}
				a:hover {
				}
				td {
				}
				th {
				}
				thead th {
				}
			</style>
		</head>
		<body>
		<div id="content">
			<h1>MMS Sitemap</h1>
			<p class="explain">This sitemap is only for use by the MMS Search engine and should not be published to
			   generic search engines. It contains far more information than is publically necessary
			   which MMS uses to detect changes on the site.
				<xsl:if test="count(sitemap:sitemapindex/sitemap:sitemap) &gt; 0">
				This Index file contains <xsl:value-of select="count(sitemap:sitemapindex/sitemap:sitemap)"/> entries.
				</xsl:if>
				<xsl:if test="count(sitemap:sitemapindex/sitemap:sitemap) &lt; 1">
				This Index contains <xsl:value-of select="count(sitemap:urlset/sitemap:url)"/> URLs.
				</xsl:if>
			</p>
			<xsl:if test="count(sitemap:sitemapindex/sitemap:sitemap) &gt; 0">
				<table id="sitemap" cellpadding="1px" cellspacing="1px">
					<thead>
					<tr style="width:100%">
						<th>Sitemap</th>
						<th>Last Change</th>
					</tr>
					</thead>
					<tbody>
					<xsl:for-each select="sitemap:sitemapindex/sitemap:sitemap">
						<xsl:variable name="sitemapURL">
							<xsl:value-of select="sitemap:loc"/>
						</xsl:variable>
						<tr>
							<td>
								<a href="{$sitemapURL}"><xsl:value-of select="sitemap:loc"/></a>
							</td>
							<td>
								<xsl:value-of select="concat(substring(sitemap:lastmod,0,11),concat(' ', substring(sitemap:lastmod,12,8)),concat(' ', substring(sitemap:lastmod,20,6)))"/>
							</td>
						</tr>
					</xsl:for-each>
					</tbody>
				</table>
			</xsl:if>
			<xsl:if test="count(sitemap:sitemapindex/sitemap:sitemap) &lt; 1">
				<table id="sitemap" cellpadding="1px" cellspacing="1px">
					<thead>
					<tr>
						<th>URL</th>
						<th>Last Change</th>
					</tr>
					</thead>
					<tbody>
					<xsl:variable name="lower" select="'abcdefghijklmnopqrstuvwxyz'"/>
					<xsl:variable name="upper" select="'ABCDEFGHIJKLMNOPQRSTUVWXYZ'"/>
					<xsl:for-each select="sitemap:urlset/sitemap:url">
						<tr>
							<td>
								<xsl:variable name="itemURL">
									<xsl:value-of select="sitemap:loc"/>
								</xsl:variable>
								<a href="{$itemURL}">
									<xsl:value-of select="sitemap:loc"/>
								</a>
							</td>
							<td>
								<xsl:value-of select="concat(substring(sitemap:lastmod,0,11),concat(' ', substring(sitemap:lastmod,12,8)),concat(' ', substring(sitemap:lastmod,20,6)))"/>
							</td>
						</tr>
					</xsl:for-each>
					</tbody>
				</table>
			</xsl:if>
			<div class="generated">
				<xsl:value-of select="//comment()[3]" />
			</div>
		</div>
		</body>
		</html>
	</xsl:template>
	</xsl:stylesheet>
