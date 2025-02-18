<tr>
    <th>
        <h3>Marketplace</h3>
    </th>
</tr>

<tr class="mobbex-color-column">
    <td>Operation Type:</td>
    <td>{$op_type}</td>
</tr>

{foreach from=$items item=product}

<tr>
    <td>Producto:</td>
    <td>{$product['name']}</td>
</tr>
<tr class="mobbex-color-column">
    <td>Cantidad:</td>
    <td>{$product['quantity']}</td>
</tr>
<tr>
    <td>Precio:</td>
    <td>${$product['total']}</td>
</tr>
<tr class="mobbex-color-column">
    <td>Nombre del vendedor</td>
    <td>{$product['vendor_name']}</td>
</tr>

<!--Only if Split Mode-->
{if $op_type eq 'split-hybrid'}
    <tr class="mobbex-color-column">
        <td>Comisión:</td>
        <td>${$product['fee']} ({$product['fee_amount']})</td>
    </tr>
    <tr>
        <td>Retener:</td>
        <td>{$product['vendor_hold']}</td>
    </tr>
{/if}

<tr class="mobbex-end-table">
    <td></td>
    <td></td>
</tr>

{/foreach}

