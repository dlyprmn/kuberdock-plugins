{include file="$template/pageheader.tpl" title=$LANG.knowledgebasetitle}

<div class="well knowledge-search">
    <div class="textcenter">
        <form method="post" action="knowledgebase.php?action=search" class="form-inline">
            <input class="bigfield" name="search" type="text" value="{$LANG.kbquestionsearchere}" onfocus="this.value=(this.value=='{$LANG.kbquestionsearchere}') ? '' : this.value;" onblur="this.value=(this.value=='') ? '{$LANG.kbquestionsearchere}' : this.value;"/>
            <input type="submit" class="send-message" value="{$LANG.knowledgebasesearch}" />
        </form>
    </div>
</div>

{include file="$template/subheader.tpl" title=$LANG.knowledgebasecategories}

<div class="row">
    <div class="container-padding-63">
        <div class="control-group">
        {foreach name=kbasecats from=$kbcats item=kbcat}
            <div class="col4">
                <div class="internalpadding">
                    <img src="{$BASE_PATH_IMG}/folder.gif" /> <a href="{if $seofriendlyurls}knowledgebase/{$kbcat.id}/{$kbcat.urlfriendlyname}{else}knowledgebase.php?action=displaycat&amp;catid={$kbcat.id}{/if}" class="fontsize2"><strong>{$kbcat.name}</strong></a> ({$kbcat.numarticles})<br />
                    {$kbcat.description}
                </div>
            </div>
            {if ($smarty.foreach.kbasecats.index+1) is div by 4}<div class="clear"></div>
            {/if}
        {/foreach}
        </div>
    </div>
    <div class="clear"></div>
</div>

{include file="$template/subheader.tpl" title=$LANG.knowledgebasepopular}

{foreach from=$kbmostviews item=kbarticle}
<div class="row">
    <div class="container-padding-63">
        <img src="{$BASE_PATH_IMG}/article.gif"> <a href="{if $seofriendlyurls}knowledgebase/{$kbarticle.id}/{$kbarticle.urlfriendlytitle}.html{else}knowledgebase.php?action=displayarticle&amp;id={$kbarticle.id}{/if}" class="fontsize2"><strong>{$kbarticle.title}</strong></a><br />
        {$kbarticle.article|truncate:100:"..."}
    </div>
</div>
{/foreach}

<br />