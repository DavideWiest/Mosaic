

<div class=" p-4 rounded-md">
    {if $fragmentContent["ImageContent"]}
        <div class="mb-4">
            <img src="data:image/jpeg;base64,{base64_encode($fragmentContent['ImageContent'])}" alt="Image" class="max-w-full h-auto rounded-lg">
        </div>
    {/if}
    <div class="mb-4">
        <p>{$fragmentContent["Description"]}</p>
    </div>
</div>
