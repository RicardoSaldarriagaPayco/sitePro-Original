
var Globals = {
	getDefaultStyles: function(isStore) {
		var styles = {
			button_color: '#ffffff',
			button_border: {
				color: "#cccccc",
				style: 'solid',
				weight: 1,
				differ: false,
				css: "1px solid #cccccc",
				cssRaw: 'border: 1px solid #cccccc'
			},
			logo: 'epayco_dark.svg',
			font_family: "Arial",
			font_size: 12,
			label_color: '#949494'
		};
		return isStore ? styles : $.extend(true, styles, {
			button_label: __('Pay with %s').replace('%s', ''),
			showlogo: true,
			logo_width: 110,
			button_padding: 4
		});
	},
	getLogosList: function() {
		return [
			{id: 'epayco_dark.svg', name: 'dark'},
			{id: 'epayco-button-blue.svg', name: 'blue'},
			{id: 'epayco-button-white.svg', name: 'white-spanish', backgroundColor: '#1e1f49'},
			{id: 'epayco-button-white-english.svg', name: 'white', backgroundColor: '#1e1f49'},
		];
	}
};

ElementRegister.registerPaymentGateway({
	name: 'ePayco',
	id: 'epayco',
	pageUrl: 'https://www.epayco.com/',
	keyFieldId: 'customerId',
	keyField2Id: 'pkey',
	keyField3Id: 'secretKey',
	keyField4Id: 'payType',
	keyField5Id: 'demo',
	keyFieldDef: {type: 'HorizontalLayout', columnWeights: [6, 6, 6, 6, 6, 6], noPadding: true, children: [
		{type: 'TextField', placeholder: 'Customer ID', id: 'key'},
		{type: 'TextField', placeholder: 'P key', id: 'key2'},
		{type: 'TextField', placeholder: 'Public Key', id: 'key3', css: {marginTop: 5}},
		{type: 'CheckBox', label: __('Test mode'), id: 'key5', css: {padding: 7, marginTop: 5, display: 'inline-block'}, init: function() {
			this.getElem().attr('title', __('For testing purpose without real payments')).tooltip({placement: 'right'});
		}}
	]},
	
	
	titleFieldId: 'label',
	nameFieldId: 'productinfo',
	priceFieldId: 'amount',
	currencyFieldId: 'currency',
	globalVars: ['customerId', 'pkey','secretKey','demo'],
	useStylesInStoreCart: true,
	defaultStyles: Globals.getDefaultStyles(true),
	styleTabDef: PluginWrapper.paymentGatewayStyleTabDef(Globals.getLogosList(), 'epayco', true),
	forceActive: true
});
PluginWrapper.registerPlugin('epayco', {
	name: 'ePayco',
	element: {
		minSize: {width: 50, height: 20},
		defaultSize: {width: 160, height: 81}
	},
	properties: PluginWrapper.paymentGatewayStylePropertiesDef([
		{id: 'productinfo', type: 'EpaycoProductInfo',
			get: function(data) { return data.content.productinfo; },
			set: function(value, data) {
				data.content.productinfo = value;
			}
		},
		{id: 'customerId', type: 'EpaycoCustomerId', helpText: 'EpaycoCustomerId helpt text .',
			get: function(data) { return data.content.customerId; },
			set: function(value, data) {
				data.content.customerId = value;
			}
		},
		{id: 'pkey', type: 'EpaycoPkey', helpText: 'ExpaycoPkey helpt text .',
			get: function(data) { return data.content.pkey; },
			set: function(value, data) {
				data.content.pkey = value;
			}
		},
		{id: 'secretKey', type: 'EpaycoSecretKey', helpText: 'EpaycoSecretKey help text.',
			get: function(data) { return data.content.secretKey; },
			set: function(value, data) {
				data.content.secretKey = value;
			}
		},
		{id: 'amount', type: 'EpaycoAmount', helpText: __("Amount to be transferred") + ', USD',
			get: function(data) { return data.content.amount; },
			validate: function(value) {
				if (/^[\d.,]+$/.test(value)) {
					return '';
				} else {
					return (__('Incorrect format'));
				}
			},
			set: function(value, data) {
				data.content.amount = value;
			}
		},
		{id: 'currency', type: 'EpaycoCurrency',
			get: function(data) { return data.content.currency; },
			set: function(value, data) {
				data.content.currency = value;
			}
		},
		{id: 'demo', type: 'EpaycoDemo',
			get: function(data) { return data.content.demo; },
			set: function(value, data) {
				data.content.demo = value;
			}
		},
	], Globals.getLogosList(), 'epayco'),
	propertyDialog: {
		noScroll: true,
		tabs: [
			{children: [
				{type: 'VerticalLayout', spacing: 15, children: [
					{type: 'HorizontalLayout', columnWeights: [9, 3], css: {marginTop: 15}, children: [
						{type: 'VerticalLayout', children: [
							{type: 'Label', text: __('Item Name')},
							{type: 'TextField', id: 'productinfo'}
						]},
						{type: 'VerticalLayout', children: [
							{type: 'Label', text: __('Amount'), helpText: __("Amount to be transferred") + ', USD'},
							{type: 'TextField', id: 'amount'}
						]},
						{type: 'VerticalLayout', children: [
						    {type: 'Label', text: __('Currency')},
						    {type: 'DropdownBox', id: 'currency', options: [
                                {id: '#USD', name: 'USD', value: 'USD'}
						    ]}
					    ]}
					]},
					{type: 'HorizontalLayout', columnWeights: [6, 6], children: [
    					{type: 'VerticalLayout', children: [
    						{type: 'Label', text: 'P_CUST_ID_CLIENTE', helpText: 'Customer ID that identifies you to ePayco. You can find it in your customer panel in the configuration option.'},
    						{type: 'TextField', id: 'customerId'}
    					]},
    					{type: 'VerticalLayout', children: [
    						{type: 'Label', text: 'P_KEY', helpText: 'Key to sign the information sent and received from ePayco. You can find it in your customer panel in the configuration option.'},
    						{type: 'TextField', id: 'pkey'}
    					]}
    				]},
    				{type: 'HorizontalLayout', columnWeights: [6, 6], css: {marginTop: 15}, children: [
    					{type: 'VerticalLayout', children: [
    						{type: 'Label', text: 'PUBLIC_KEY', helpText: 'Key to authenticate and consume ePayco services, Provided in your customer panel in the configuration option.'},
    						{type: 'TextField', id: 'secretKey'}
    					]}
    				]},
    				{type: 'VerticalLayout', css: {marginTop: 15}, children: [
    					{type: 'CheckBox', id: 'demo', css: {display: 'inline-block'}, label: __('Test mode'), init: function() {
    						this.getElem().attr('title', __('For testing purpose without real payments'));
    						this.getElem().tooltip({placement: 'right'});
    					}}
    				]}
				]}
			]},
			PluginWrapper.paymentGatewayStyleTabDef(Globals.getLogosList(), 'epayco')
		]
	},
	openAction: function (fields, data, elem) {
	    var itm;
		fields.productinfo.setText(data.content.productinfo);
		fields.amount.setText(data.content.amount);
		fields.customerId.setText(data.content.customerId);
		fields.pkey.setText(data.content.pkey);
		fields.secretKey.setText(data.content.secretKey);
		itm = fields.currency.getItemById('#' + data.content.currency);
		fields.currency.selectItem(itm);
		fields.demo.setValue(data.content.demo);
		PluginWrapper.paymentGatewayOpenAction(fields, data);
	},
	applyAction: function (fields, data, elem) {
	    var itm;
		data.content.productinfo = fields.productinfo.getText();
		data.content.amount = fields.amount.getText();
		data.content.customerId = fields.customerId.getText();
		data.content.pkey = fields.pkey.getText();
		data.content.secretKey = fields.secretKey.getText();
		itm = fields.currency.getSelectedItem();
		data.content.currency = itm.getOriginal().value;
		data.content.demo = fields.demo.getValue();
		PluginWrapper.paymentGatewayApplyAction(fields, data);
	},
	loadAction: function (data) {
	    data.content.__globalVars = ['demo'];
		if (!data.content.productinfo) data.content.productinfo = '';
		if (!data.content.amount) data.content.amount = '1';
		if (!data.content.customerId) data.content.customerId = '';
		if (!data.content.pkey) data.content.pkey = '';
		if (!data.content.secretKey) data.content.secretKey = '';
		if (!data.content.currency) data.content.currency = 'USD';
		if (data.content.demo === undefined) data.content.demo = false;
		if (['epayco.png'].indexOf(data.content.logo) > -1) {
			data.content.logo = 'epayco_dark.svg';
		}
		PluginWrapper.paymentGatewayLoadAction(data, Globals.getDefaultStyles());
	}
});