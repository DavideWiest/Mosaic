<div class="md:grid md:grid-cols-3">
    {for $i=0 to count($plans)-1}
        <div class="p-1 m-1 md:m-3">
            {include file="larger_components/plan.tpl" plan=$plans[$i] planperm=$planperms[$i] planColumnTypeData=$planColumnTypeData planpermColumnTypeData=$planpermColumnTypeData}
        </div>
    {/for}
</div>