
function initAccessoriesAutocomplete(){

		$('#product_autocomplete_input')
			.autocomplete('ajax_products_list.php',{
				minChars: 1,
				autoFill: true,
				max:20,
				matchContains: true,
				mustMatch:true,
				scroll:false,
				cacheLength:0,
				formatItem: function(item) {
					return item[1]+' - '+item[0];
				}
			}).result(addAccessory);
		
		$('#product_autocomplete_input').setOptions({
			extraParams: {
				excludeIds : getAccessoriesIds()
			}
		});
	}

    function getAccessoriesIds()
    {
        if ($('#inputAccessories').val() === undefined) return '';
        ids = $('#inputAccessories').val().replace(/\-/g,',');
        return ids;
    }

	function addAccessory(event, data, formatted)
	{
		if (data == null)
			return false;
		var productId = data[1];
		var productName = data[0];

		var $divAccessories = $('#divAccessories');
		var $inputAccessories = $('#inputAccessories');
		var $nameAccessories = $('#nameAccessories');

		/* delete product from select + add product line to the div, input_name, input_ids elements */
        $divAccessories.html($divAccessories.html() + '<div class="form-control-static"><button type="button" class="delAccessory btn btn-default" name="' + productId + '"><i class="icon-remove text-danger"></i></button>&nbsp;'+ productName +'</div>');

        $nameAccessories.val($nameAccessories.val() + productName + 'Â¤');
        $inputAccessories.val($inputAccessories.val() + productId + '-');
		$('#product_autocomplete_input').val('');
		$('#product_autocomplete_input').setOptions({
			extraParams: {excludeIds : getAccessoriesIds()}
		});
	}

	function delAccessory(id)
	{
        var div = getE('divAccessories');
        var input = getE('inputAccessories');
        var name = getE('nameAccessories');

        // Cut hidden fields in array
        var inputCut = input.value.split('-');
        var nameCut = name.value.split('Â¤');

        if (inputCut.length != nameCut.length)
            return jAlert('Bad size');

        // Reset all hidden fields
        input.value = '';
        name.value = '';
        div.innerHTML = '';
        for (i in inputCut)
        {
            // If empty, error, next
            if (!inputCut[i] || !nameCut[i])
                continue ;

            // Add to hidden fields no selected products OR add to select field selected product
            if (inputCut[i] != id)
            {
                input.value += inputCut[i] + '-';
                name.value += nameCut[i] + 'Â¤';
                div.innerHTML += '<div class="form-control-static"><button type="button" class="delAccessory btn btn-default" name="' + inputCut[i] +'"><i class="icon-remove text-danger"></i></button>&nbsp;' + nameCut[i] + '</div>';
            }
            else
                $('#selectAccessories').append('<option selected="selected" value="' + inputCut[i] + '-' + nameCut[i] + '">' + inputCut[i] + ' - ' + nameCut[i] + '</option>');
        }

		$('#product_autocomplete_input').setOptions({
			extraParams: {excludeIds : getAccessoriesIds()}
		});
	}





