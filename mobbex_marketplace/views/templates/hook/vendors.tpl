<div class="col-md-12 align-items-center" style="margin-top: 2em;">
    <h2 class="mr-3" style="display: inline-block;">Marketplace</h2>
</div>
<div class="row col-md-12 form-group mbbx-mrkt-cfg {if !$marketplace}hidden{/if}">
    <div class="col-md-12">
        <label for="mbbx_vendor">Seleccione un vendedor:</label>
        <p>Selecciona el vendedor correspondiente a este articulo/categoria</p>
    </div>
    <div class="col-md-3">
        <fieldset>
            <select id="mbbx_vendor" name="mbbx_vendor" class="custom-select">
                <option value="" {if !isset($currentVendor)}selected{/if}>--Selecciona Vendedor--</option>
                {foreach from=$vendors key=key item=vendor}
                    <option value="{$vendor['id']}" {if $currentVendor == $vendor['id']}selected{/if}>{$vendor['name']}</option>
                {/foreach}
            </select>
        </fieldset>
    </div>
    <div class="col-md-12 mbbx-mrkt-cfg">
        <label for="mbbx_fee">Comisión</label>
        <p>La comisión que se le cobrará al vendedor, si corresponde. Use el signo % para cobrar en porcentaje. Ej. "10%"</p>
    </div>
    <div class="col-md-3">
        <fieldset>
            <input type="text" name="mbbx_fee" id="mbbx_fee" value="{$fee}">
        </fieldset>
    </div>
</div>