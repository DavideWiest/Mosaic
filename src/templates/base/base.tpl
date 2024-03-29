
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <base href="~/" />

    <link href="{BusinessConstants::$STATIC_URL_PREFIX}/css/site.css" rel="stylesheet" />

    <link rel="icon" type="image/png" href="{BusinessConstants::$STATIC_URL_PREFIX}/assets/favicon.png" />

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:ital,wght@0,100..800;1,100..800&family=Inter:wght@200;300;400;500;600&family=Merriweather:wght@300;400;700;900&family=Roboto:ital,wght@0,400;0,500;0,700;1,400;1,500&display=swap"
        rel="stylesheet">        

    <script src="https://cdn.tailwindcss.com"></script>
    <script src="{BusinessConstants::$STATIC_URL_PREFIX}/js/twConfig.js"></script>

    {block name=head}{/block}
    
</head>

<body class="bg-bgcol text-gray-400">
    {if !isset($redirectToFront)}
        {assign var="redirectToFront" value=false}
    {/if}
    {if !isset($maxWidth)}
        {assign var="maxWidth" value="1200px"}
    {/if}
    {if !isset($withNav)}
        {assign var="withNav" value=true}
    {/if}
    {if !isset($withFooter)}
        {assign var="withFooter" value=true}
    {/if}

    {if $withNav}
        {include file="base/header.tpl"}
    {/if}

    {include file="base/messages.tpl"}
    <div class="flex justify-center">
        <div class="mx-4 md:mx-6" style="max-width: {$maxWidth}; width: 100%;">
            {block name=content}{/block}
        </div>
    </div>

    {if $withFooter}
        {include file="base/footer.tpl"}
    {/if}

    <script src="{BusinessConstants::$STATIC_URL_PREFIX}/js/baseDomManagement.js"></script>
</body>

</html>