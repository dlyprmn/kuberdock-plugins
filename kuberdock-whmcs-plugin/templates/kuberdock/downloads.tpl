{include file="$template/pageheader.tpl" title=$LANG.downloadstitle}

<div class="well knowledge-search">
    <div class="textcenter">
        <form method="post" action="downloads.php?action=search">
            <input type="text" name="search" value="{$LANG.downloadssearch}" class="bigfield" style="margin-bottom: 0;" onfocus="if(this.value=='{$LANG.downloadssearch}')this.value=''" />
            <input type="submit" class="send-message" value="{$LANG.search}">
        </form>
        <div class="clear"></div>
        <br>
        <p align="center">{$LANG.downloadsintrotext}</p>
    </div>
</div>

{include file="$template/subheader.tpl" title=$LANG.downloadscategories}

<div class="row">
<div class="control-group">
{foreach from=$dlcats item=dlcat}
    <div class="col4">
        <div class="internalpadding">
            <img src="{$BASE_PATH_IMG}/folder.gif" /> <a href="{if $seofriendlyurls}downloads/{$dlcat.id}/{$dlcat.urlfriendlyname}{else}downloads.php?action=displaycat&amp;catid={$dlcat.id}{/if}" class="fontsize2"><strong>{$dlcat.name}</strong></a> ({$dlcat.numarticles})<br />
            {$dlcat.description}
        </div>
    </div>
{/foreach}
</div>
</div>

{include file="$template/subheader.tpl" title=$LANG.downloadspopular}

{foreach from=$mostdownloads item=download}
<div class="row">
    {$download.type} <a href="{$download.link}" class="fontsize2"><strong>{$download.title}{if $download.clientsonly} <img src="{$BASE_PATH_IMG}/padlock.gif" alt="{$LANG.loginrequired}" />{/if}</strong></a><br />
    {$download.description}<br />
    <span class="lighttext">{$LANG.downloadsfilesize}: {$download.filesize}</span>
</div>
{/foreach}
