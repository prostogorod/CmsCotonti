<!-- BEGIN: MAIN -->

	<div class="mboxHD">{FORUMS_EDITPOST_PAGETITLE}</div>
	<div class="mboxBody">

		<div id="subtitle">{FORUMS_EDITPOST_SUBTITLE}</div>

		<!-- BEGIN: FORUMS_EDITPOST_ERROR -->
		<div class="error">{FORUMS_POSTS_EDITPOST_ERROR_BODY}</div>
		<!-- END: FORUMS_EDITPOST_ERROR -->

		<form action="{FORUMS_EDITPOST_SEND}" method="post" name="editpost">
			<div class="tCap2"></div>
			<table class="cells" border="0" cellspacing="1" cellpadding="2">
				<tr>
					<td><div style="width:100%;">
<!-- BEGIN: FORUMS_EDITPOST_FIRSTPOST -->
{PHP.L.Topic}: <input type="text" class="text" name="rtopictitle" value="{FORUMS_EDITPOST_TOPICTITTLE}" size="56" maxlength="64" /><br/>
{PHP.L.Description}: <input type="text" class="text" name="rtopicdesc" value="{FORUMS_EDITPOST_TOPICDESCRIPTION}" size="56" maxlength="64" /><br />
<!-- END: FORUMS_EDITPOST_FIRSTPOST -->
<textarea class="editor" name="rtext" rows="20" cols="56">{FORUMS_EDITPOST_TEXT}</textarea>{FORUMS_EDITPOST_MYPFS}</div></td>
				</tr>
				<!-- BEGIN: FORUMS_EDITPOST_TAGS -->
				<tr>
				<td>{FORUMS_EDITPOST_TOP_TAGS}: {FORUMS_EDITPOST_FORM_TAGS} ({FORUMS_EDITPOST_TOP_TAGS_HINT})</td>
				</tr>
				<!-- END: FORUMS_EDITPOST_TAGS -->
				<tr>
					<td class="valid"><input type="submit" value="{PHP.skinlang.forumseditpost.Update}" /></td>
				</tr>
			</table><div class="bCap"></div>
		</form>
	</div>

<!-- END: MAIN -->