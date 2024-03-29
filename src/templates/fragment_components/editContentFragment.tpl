

<div>
    <label for="Position">Position:</label>
    <input type="number" id="Position" name="Position" placeholder="Enter Position" value="{$subsiteCf['Position']}">
</div>
<div>
    <label for="BackgroundColor">Background Color (HEX):</label>
    <input type="color" id="BackgroundColor" name="BackgroundColor" value="#{$subsiteCf['BackgroundColor']}">
</div>
<div>
    <label for="Opacity">Background Opacity:</label>
    <input type="range" min="0.0" max="1.0" id="Opacity" name="Opacity" step="0.01" value="{if (isset($subsiteCf['Opacity']) && $subsiteCf['Opacity'] != '')}{$subsiteCf['Opacity']}{else}0{/if}">
</div>
<div>
    <label for="BackgroundImage">Background Image:</label>
    {if (isset($subsiteCf['BackgroundImage']) && $subsiteCf['BackgroundImage'] != '')}
        {if GenericRender::InsertValuePlainly($subsiteCf, 'BackgroundImage') != ""}
            <img class="h-12 inline-block" src="data:image/jpeg;base64,{GenericRender::InsertValuePlainly($subsiteCf, 'BackgroundImage', 'img')}" alt="Background Image">
        {/if}
    {/if}
    <input type="file" id="BackgroundImage" name="BackgroundImage" accept=".jpg, .jpeg">
</div>