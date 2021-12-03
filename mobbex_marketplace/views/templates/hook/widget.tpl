<tr>
    <th>
        <h3>Marketplace</h3>
    </th>
</tr>

{foreach from=$data item=product}

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
<tr>
<td>CUIT</td>
<td>{$product['vendor_tax_id']}</td>
</tr>
<tr class="mobbex-color-column">
<td>Comisión:</td>
<td>${$product['fee']} ({$product['fee_amount']}%)</td>
</tr>
<tr class="mobbex-end-table">
<td>Retener:</td>
<td>{$product['vendor_hold']}</td>
</tr>

{/foreach}

