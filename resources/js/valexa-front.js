require('./bootstrap');

window.app = new Vue(
{
	el: '#app',
	data: {
		appName: props.appName || '',
		route: props.currentRouteName,
		cartItems: parseInt(localStorage.getItem('cartItems')) || 0,
		cart: JSON.parse(localStorage.getItem('cart')) || [],
		trasactionMsg: props.trasactionMsg || '',
		product: props.product || {},
		products: props.products || {},
		licenseId: props.licenseId || null,
		favorites: {},
		liveSearchItems: [],
		recentlyViewedItems: {},
		activeScreenshot: props.activeScreenshot,
		couponRes: {status: false},
		couponValue: 0,
		paymentProcessor: props.paymentProcessor || '',
		paymentFees: props.paymentFees,
		minimumPayments: props.minimumPayments,
		customAmount: '',
		minimumItemPrice: null,
		customItemPrice: null,
		totalAmount: 0,
		location: props.location,
		locale: 'en',
		subscriptionId: props.subscriptionId || null,
		subscriptionPrice: props.subscriptionPrice || null,
		paymentProcessors : props.paymentProcessors,
		subcategories: props.subcategories,
		categories: props.categories,
		countryNames: props.countryNames || [],
		pages: props.pages,
		itemId: props.itemId || null,
		itemPrices: props.itemPrices || null,
		itemPromoPrice: null,
		itemHasPromo: false,
		currency: props.currency,
		folderFileName: null,
		folderClientFileName: null,
		usersReactions: {},
		usersReaction: '',
		userMessage: '',
		cookiesAccepted: true,
		replyTo: {userName: null, commentId: null},
		folderContent: null,
		parsedQueryString: {},
		translation: props.translation || {},
		userCurrency: props.userCurrency,
		currencyPos: props.currency.position,
		exchangeRate: props.exchangeRate,
		currencyDecimals: props.currencyDecimals,
		currencies: props.currencies,
		couponFormVisible: false,
		guestItems: {},
		keycodes: props.keycodes || {},
		guestAccessToken: '',
		usersNotifRead: null,
		usersNotif: props.usersNotif || '',
		userNotifs: props.userNotifs || [],
		hasNotifPermission: false,
		previewIsPlaying: false,
		previewTrack: null,
		menu: {
			mobile: {
				type: null,
				selectedCategory: null,
				submenuItems: null,
				hidden: true
			},
			desktop: {
				selectedCategory: null,
				submenuItems: null,
				itemsMenuPopup: {top: 97, left: 0}
			}
		}
	},
	methods: {
		mainMenuBack: function()
		{
			Vue.set(this.menu, 'mobile', Object.assign({... this.menu.mobile}, {
									type: this.menu.mobile.selectedCategory !== null ? 'categories' : null,
									selectedCategory: null,
									submenuItems: this.menu.mobile.selectedCategory !== null ? this.categories : null
								}));
		},
		setSubMenu: function(e, categoryIndex, mobileMenu = false, type = null)
		{
			if(categoryIndex === null)
				return;

			if(mobileMenu)
			{
				this.menu.mobile.type = type;

				if(type === 'categories')
				{
					this.menu.mobile.submenuItems = this.categories;
				}
				else if(type === 'subcategories')
				{
					if(Object.keys(this.subcategories).indexOf(categoryIndex.toString()) >= 0)
					{
						this.menu.mobile.selectedCategory = this.categories[categoryIndex];
						this.menu.mobile.submenuItems = this.subcategories[categoryIndex];
					}
					else
					{
						this.menu.mobile.type = 'categories';
						
						this.location.href = this.setProductsRoute(this.categories[categoryIndex].slug);
					}
				}
				else if(type === 'pages')
				{
					this.menu.mobile.selectedCategory = null;
					this.menu.mobile.submenuItems = this.pages;
				}
				else if(type === 'languages')
				{
					this.menu.mobile.selectedCategory = null;
					this.menu.mobile.submenuItems = null;
				}
			}
			else
			{ 
				var isSame = categoryIndex == getObjectProp(this.menu.desktop.selectedCategory, 'id');

				if(Object.keys(this.subcategories).indexOf(categoryIndex.toString()) >= 0)
				{
					Vue.set(this.menu, 'desktop', Object.assign({... this.menu.desktop}, {
										selectedCategory: isSame ? null : this.categories[categoryIndex],
										submenuItems: isSame ? null : this.subcategories[categoryIndex],
										itemsMenuPopup: {top: 97, left: e.target.getBoundingClientRect().left}
									}));
				}
				else
				{					
					this.location.href = this.setProductsRoute(this.categories[categoryIndex].slug);
				}
			}
		},
		setPaymentProcessor: function(method)
		{
			this.paymentProcessor = method;
		},
		checkout: function(e)
		{
			if(this.totalAmount > 0)
			{
				if(this.paymentProcessor)
				{
					e.target.disabled = true;

					if(this.paymentProcessor != 'authorize_net')
					{
						this.trasactionMsg = 'processing';
					}

					if(this.paymentProcessor === 'stripe' && this.paymentProcessors.stripe)
					{
						var payload = {
							processor: "stripe",
							coupon: this.couponRes.status ? this.couponRes.coupon.code : null,
							custom_amount: this.customAmount
						};

						var route = this.subscriptionId !== null ? props.routes.subscriptionPayment : props.routes.payment;

						if(this.subscriptionId == null)
						{
							payload.cart = 	JSON.stringify(app.cart);
						}
						else
						{
							payload.subscription_id = this.subscriptionId;
						}

						try
						{
							$.post(route, payload, null, 'json')
							.done(function(data)
							{
								if(data.hasOwnProperty('user_message'))
								{
									app.showUserMessage(data.user_message, e);

					  			return;
								}

								if(data.status)
								{
									location.href = data.redirect;

									return;
								}

								stripe.redirectToCheckout({sessionId: data.id})
								.then(function(result) 
								{
									app.showUserMessage(result.error.message, e);
								});
							})
						}
						catch(err)
						{
							app.showUserMessage(err, e);
						}
					}
					else if(this.paymentProcessor === 'payhere' && this.paymentProcessors.payhere)
					{
						var payload = {
							processor: "payhere",
							coupon: this.couponRes.status ? this.couponRes.coupon.code : null,
							custom_amount: this.customAmount
						};

						var formData = decodeURIComponent($('#form-checkout').serialize()).split('&').reduce((acc, prop) => 
						{
						    var prop = prop.split('=');
						    
						    acc[prop[0]] = prop[1];

								return acc;
						}, {});

						payload = Object.assign(payload, formData);

						var route = this.subscriptionId !== null ? props.routes.subscriptionPayment : props.routes.payment;

						if(this.subscriptionId == null)
						{
							payload.cart = 	JSON.stringify(app.cart);
						}
						else
						{
							payload.subscription_id = this.subscriptionId;
						}

						try
						{
					    // Called when user completed the payment. It can be a successful payment or failure
					    payhere.onCompleted = function onCompleted(orderId)
					    {
						    	var status = 'processing';
						    	var payload = {"order_id": orderId, "processor": app.paymentProcessor, "async": true};

									(function myLoop(i) {
									  setTimeout(function() 
									  {	
												$.post('/checkout/payment/order_completed', payload)
									  		.done(function(data)
									  		{
									  			status = data.status;

									  			if(status === false)
									  			{
									  				app.showUserMessage(data.user_message, e);
									  			}
									  			else if(status === true)
									  			{
									  				Vue.nextTick(function()
									  				{
									  					location.href = data.redirect_url;
									  				})
									  			}
									  		})
									  		.fail(function(data)
									  		{
									  			status = null;

									  			app.showUserMessage(data.responseJSON.message, e);
									  		})

									    if (--i && status === 'processing') myLoop(i);
									  }, 5000)
									})(5);
							}

					    // Called when user closes the payment without completing
					    payhere.onDismissed = function onDismissed() 
					    {
				  				app.trasactionMsg = '';
				  				e.target.disabled = false;

					  			return;
					    };

					    // Called when error happens when initializing payment such as invalid parameters
					    payhere.onError = function onError(error)
					    {
					    		app.showUserMessage(error, e);

					  			return;
					    };

							$.post(route, payload, null, 'json')
							.done(function(data)
							{
								if(data.hasOwnProperty('user_message'))
								{
									app.showUserMessage(data.user_message, e);

					  			return;
								}

								payhere.startPayment(data.payload);
							})
							.fail(function(data)
							{
									app.showUserMessage(data.responseJSON.message, e);
							})
						}
						catch(err)
						{
								app.showUserMessage(err, e);
						}
					}
					else if(this.paymentProcessor === 'spankpay' && this.paymentProcessors.spankpay)
					{
						var payload = {
							processor: "spankpay",
							coupon: this.couponRes.status ? this.couponRes.coupon.code : null,
							custom_amount: this.customAmount
						};

						var route = this.subscriptionId !== null ? props.routes.subscriptionPayment : props.routes.payment;

						if(this.subscriptionId == null)
						{
							payload.cart = 	JSON.stringify(app.cart);
						}
						else
						{
							payload.subscription_id = this.subscriptionId;
						}

						try
						{
							$.post(route, payload, null, 'json')
							.done(function(data)
							{
								if(data.hasOwnProperty('user_message'))
								{
									app.showUserMessage(data.user_message, e);

					  			return;
								}

								if(data.status)
								{
									Spankpay.show({
								    apiKey: data.public_key,
								    fullscreen: false,
								    amount: data.amount,
								    currency: data.currency,
								    callbackUrl: `${location.protocol}//${location.hostname}/checkout/webhook?order_id=${data.order_id}`,
								    onPayment: function(payment) 
								    {
										    $.post(`${location.protocol}//${location.hostname}/checkout/payment/order_completed`, 
										    	{payment})
										    .done(function(data)
										    {
										    	location.href = data.redirect_url || '';
										    })	
										},
										onOpen: function()
										{

										},
										onClose: function()
										{
											app.trasactionMsg = '';
						  				e.target.disabled = false;

							  			return;
										}
									})
								}
							})
						}
						catch(err)
						{
							app.showUserMessage(err, e);
						}
					}
					else if(this.paymentProcessor === 'omise' && this.paymentProcessors.omise)
					{
						OmiseCard.configure({
					    publicKey: omisePublicKey
					  });

					  var payload = {
							processor: "omise",
							coupon: this.couponRes.status ? this.couponRes.coupon.code : null,
							custom_amount: this.customAmount,
							prepare: true
						};

						var route = this.subscriptionId !== null ? props.routes.subscriptionPayment : props.routes.payment;

						if(this.subscriptionId == null)
						{
							payload.cart = 	JSON.stringify(app.cart);
						}
						else
						{
							payload.subscription_id = this.subscriptionId;
						}

						$.post(route, payload, null, 'json')
						.done(function(data)
						{
							if(data.hasOwnProperty('user_message'))
							{
								app.showUserMessage(data.user_message, e);

				  			return;
							}

							if(data.status)
							{
								var form = document.querySelector('#form-checkout');

								OmiseCard.open({
						      amount: data.amount,
						      currency: data.currency,
						      defaultPaymentMethod: "credit_card",
						      onCreateTokenSuccess: (nonce) => 
						      {
						          if(nonce.startsWith("tokn_")) 
						          {
						              form.omiseToken.value = nonce;
						          }
						          else
						          {
						              form.omiseSource.value = nonce;
						          }
						        	
						        	form.submit();
						      },
						      onFormClosed: () => 
						      {
								    app.trasactionMsg = '';
								    e.target.disabled = false;
								  }
						    });
							}
						})
					}
					else if(this.paymentProcessor === 'authorize_net' && this.paymentProcessors.authorize_net)
					{
						window.authorizeNetResponseHandler = function(response)
						{
							if(response.messages.resultCode === "Error")
							{
								var i = 0;
								var errors = [];

								while(i < response.messages.message.length)
								{
									errors.push(`${response.messages.message[i].code} : ${response.messages.message[i].text}`);
								  i = i + 1;
								}

								app.showUserMessage(errors.join(','), e);

								e.preventDefault();
								return false;
							}
							else if(response.messages.resultCode === "Ok")
							{
								app.trasactionMsg = 'processing';

								var payload = {
									processor: "authorize_net",
									coupon: app.couponRes.status ? app.couponRes.coupon.code : null,
									custom_amount: app.customAmount
								};

								var route = app.subscriptionId !== null ? props.routes.subscriptionPayment : props.routes.payment;

								if(app.subscriptionId == null)
								{
									payload.cart = 	JSON.stringify(app.cart);
								}
								else
								{
									payload.subscription_id = app.subscriptionId;
								}

								payload = Object.assign(payload, response);

								$.post(route, payload, null, 'json')
								.done(function(data)
								{
									if(data.hasOwnProperty('user_message'))
									{
										app.showUserMessage(data.user_message, e);

						  			return;
									}

									if(data.status)
									{
										location.href = data.redirect_url;
									}
								})
							}
							else
							{
								e.target.disabled = false;
								app.trasactionMsg = '';
							}
						}

						Vue.nextTick(function()
						{
							$('#AcceptUIBtn').click()
						})

						e.preventDefault();
						return false;
					}
					else
					{
						$('#form-checkout').submit();
					}
				}
			}
			else
			{
				this.paymentProcessor = "n-a";

				Vue.nextTick(function()
				{
					$('#form-checkout').submit();
				})
			}
		},
		showUserMessage: function(message, e = null)
		{
			app.userMessage = message;

			Vue.nextTick(function()
			{
				$('#user-message').modal({
					onHidden: function()
					{
						app.trasactionMsg = '';

						if(e !== null)
							e.target.disabled = false;
					}
				})
				.modal('show')
			});
		},
		buyNow: function(item, e)
		{
			this.addToCartAsync(item, e, () => { location.href = props.routes.checkout || '' });
		},
		addToCart: function()
		{
			this.cartItems = (parseInt(localStorage.getItem('cartItems')) || 0)+1;

			var localStorageCart = (JSON.parse(localStorage.getItem('cart')) || []);

			if(localStorageCart.length)
			{
				this.cart = localStorageCart;

				this.cart.push(this.product);

				this.saveCartChanges();

				this.updateCartPrices();

				return;
			}

			this.cart.push(this.product);

			this.saveCartChanges();

			this.updateCartPrices();
		},
		addToCartAsync: function(item, e, callback = null)
		{
			$(e.target).transition('pulse')

			Vue.nextTick(function()
			{
				if(!isNaN(app.customItemPrice))
				{
					item.custom_price = app.customItemPrice;
				}
				
				$.post(props.routes.addToCartAsyncRoute, {item}, null, 'json')
				.done((data) =>
				{
					app.product = data.product;
					app.addToCart();

					app.$forceUpdate();

					try
					{
						Vue.nextTick(function()
						{
							callback != null ? callback() : null;
						})
					}
					catch(e){}
				})	
			})
		},
		removeFromCart: function(productId)
		{
			var indexOfProduct = 	this.getProductIndex(productId);

			this.cartItems = (this.cartItems - 1);

			this.cart.splice(indexOfProduct, 1);

			this.saveCartChanges();
		},
		getProductIndex: function(productId, fromVueCart = true)
		{
			if(fromVueCart)
			{
				return 	this.cart.reduce(function(acc, currval) {
									return acc.concat(currval.id)
								}, []).indexOf(productId);
			}
			else
			{
				var localStorageCart = (JSON.parse(localStorage.getItem('cart')) || []);

				if(!localStorageCart.length)
					return -1;

				return 	localStorageCart.reduce(function(acc, currval) {
									return acc.concat(currval.id)
								}, []).indexOf(productId);
			}
		},
		saveCartChanges: function()
		{
			this.totalAmount = this.getTotalAmount();

			localStorage.setItem('cart', JSON.stringify(this.cart));
			localStorage.setItem('cartItems', this.cartItems);
		},
		updateCartItem: function(indexOfProduct)
		{
			this.cart.splice(indexOfProduct, 1, this.cart[indexOfProduct]);
		},
		applyCoupon: function(event)
		{
			var input  = event.target.previousElementSibling;
			var coupon = input.value;

			if(!coupon.length)
				return false;
			
			$.post(props.routes.coupon, 
				{
					coupon: coupon,
					for: this.subscriptionId ? 'subscription' : 'products',
					products: app.cart,
					subscription_id: this.subscriptionId
				}, 
				null, 'json')
			.done(function(res)
			{
				app.couponRes = res;

				if(res.status)
				{
					app.couponValue = Number.parseFloat(res.coupon.discount).toFixed(app.currencyDecimals);

					app.totalAmount = app.getTotalAmount();

					app.removeFromCart = function() { return false };

					app.applyCoupon = function() { return false };
				}
			})
		},
		removeCoupon: function()
		{
			location.reload();
		},
		getPaymentFee: function()
		{
			if(this.paymentFees.hasOwnProperty(this.paymentProcessor) && this.totalAmount > 0)
			{
				return Number(this.paymentFees[this.paymentProcessor]).toFixed(this.currencyDecimals);
			}

			return Number(0).toFixed(this.currencyDecimals);
		},
		getTotalAmount: function()
		{
			var paymentFee  = parseFloat(this.getPaymentFee());
			var grossAmount = 0

			if(!isNaN(this.subscriptionId) && this.subscriptionId !== null)
			{
				grossAmount += parseFloat(this.subscriptionPrice);
			}
			else
			{
				for(var item of this.cart)
				{
					grossAmount += parseFloat(item.price);
				}
			}

			if(grossAmount > 0)
			{
				var couponValue = Number.parseFloat(app.couponValue).toFixed(app.currencyDecimals);
					  grossAmount = Number.parseFloat(grossAmount + (parseFloat(!isNaN(paymentFee) ? paymentFee : 0)))
					  							.toFixed(this.currencyDecimals);

				return Number.parseFloat(grossAmount - app.couponValue).toFixed(this.currencyDecimals);
			}

			return grossAmount;
		},
		slideScreenhots: function(slideDirection)
		{
			var screenshots 	 = this.product.screenshots;
			var screenshotsLen = this.product.screenshots.length;
			var activeIndex 	 =  screenshots.indexOf(app.activeScreenshot);

			if(slideDirection === 'next')
			{
				if((activeIndex+1) < screenshotsLen)
					this.activeScreenshot = screenshots[activeIndex+1]
				else
					this.activeScreenshot = screenshots[0]
			}
			else
			{
				if((activeIndex-1) >= 0)
					this.activeScreenshot = screenshots[activeIndex-1]
				else
					this.activeScreenshot = screenshots[screenshotsLen-1]
			}
		},
		setProductsRoute: function(categorySlug)
		{
			return `${props.routes.products}/${categorySlug}`;
		},
		setPageRoute: function(pageSlug)
		{
			return `${props.routes.pages}/${pageSlug}`;
		},
		downloadItem: function(itemId, formSelector = '#download-form')
		{
			if(!itemId)
				return;

			this.itemId = itemId;

			this.$nextTick(function()
			{
				$(formSelector).submit();
			})
		},
		downloadLicense: function(itemId, formSelector)
		{
			if(!itemId)
				return;

			this.itemId = itemId;

			this.$nextTick(function()
			{
				$(formSelector).submit();
			})
		},
		downloadFile: function(folderFileName, folderClientFileName, formSelector)
		{
			this.folderFileName = folderFileName;
			this.folderClientFileName = folderClientFileName;

			this.$nextTick(function()
			{
				$(formSelector).submit();
			})
		},
		toggleMobileMenu: function()
		{
			Vue.set(this.menu, 'mobile', Object.assign({... this.menu.mobile}, {
								type: null,
								selectedCategory: null,
								submenuItems: null,
								hidden: $('#mobile-menu').isVisible()
							}));

			$('#mobile-menu').transition('fly right', function()
			{
				$('html').toggleClass('overflow-hidden')
			});
		},
		toggleItemsMenu: function()
		{
			$('#items-menu').transition('drop');
		},
		toggleMobileSearchBar: function()
		{
			$('#mobile-search-bar').transition('drop');
		},
		collectionToggleItem: function(e, id)
		{
			if(localStorage.hasOwnProperty('favorites'))
			{
				var favs = JSON.parse(localStorage.getItem('favorites'));

				if(Object.keys(favs).indexOf(String(id)) >= 0)
				{
					var newFavs = Object.keys(favs).reduce((c, v) => {
													if(v != String(id))
														c[v] = favs[v];

													return c;
												}, {});

					localStorage.setItem('favorites', JSON.stringify(newFavs));

					this.favorites = newFavs;

					$(e.target).toggleClass('active', false);
				}
				else
				{
					favs[id] = this.products[id];

					localStorage.setItem('favorites', JSON.stringify(favs));

					$(e.target).toggleClass('active', true);
				}
			}
		},
		itemInCollection: function(id)
		{
			return Object.keys(this.favorites).indexOf(String(id)) >= 0;
		},
		logout: function()
		{
			$('#logout-form').submit();
		},
		setReplyTo: function(userName, commentId)
    {
      this.replyTo = {userName, commentId};

      $('#item .column.l-side .support textarea').focus();
    },
    resetReplyTo: function()
    {
      this.replyTo = {userName: null, commentId: null};
    },
    toggleCouponForm: function()
    {
    	this.couponFormVisible = !this.couponFormVisible;
    },
    getFolderContent: function()
    {
    	var _this = this;

    	if(this.folderContent === null)
    	{
    		$.post(props.routes.productFolder, {"slug": this.product['slug'], "id": this.product['id']}, null, 'json')
    		.done(function(data)
    		{
    			if(data.hasOwnProperty('files'))
    			{
    				_this.folderContent = data.files;
    			}
    		})
    	}
    },
    getFolderFileIcon: function(fileObj)
    {
    	var fileMimeType = fileObj.mimeType;

    	if(/(text\/plain|txt)/i.test(fileMimeType))
    	{
    		return 'file alternate outline';
    	}
    	else if(/(image\/.+|\.(png|jpg|jpeg))/i.test(fileMimeType))
    	{
    		return 'file image outline';
    	}
    	else if(/zip|rar|archive|7z/i.test(fileMimeType))
    	{
    		return 'file archive outline';
    	}
    	else
    	{
    		return 'file outline';
    	}
    },
    setLocale: function(locale)
    {
    	this.locale = locale;

    	Vue.nextTick(function()
    	{
    		$('#set-locale').submit();
    	})
    },
    applyPriceRange: function(e)
    {
    	var form 			= $(e.target).closest('.form'),
    			minPrice  = form.find('input[name="min"]').val().trim(),
    			maxPrice  = form.find('input[name="max"]').val().trim();

    	if(minPrice < 0 || maxPrice < 0 || maxPrice < minPrice || minPrice === '' || maxPrice === '')
    	{    		
    		e.preventDefault();
    		return;
    	}

    	this.parsedQueryString.price_range = `${minPrice},${maxPrice}`;

    	this.location.href = queryString.stringifyUrl({url: this.location.href, query: this.parsedQueryString});
    },
    __: function(key, params = {})
    {
    	var string = this.translation[key] || key;

    	if(Object.keys(params).length)
    	{
    		for(var k in params)
    		{
    			string = string.replace(`:${k}`, params[k]);
    		}
    	}

    	return string;
    },
    price: function(price, free = false, k = false)
    {
    	if(price > 0)
    	{
    		var currencyCode = this.userCurrency ? this.userCurrency : this.currency.code;
    		var price 			 = Number(price).toFixed(this.currencyDecimals);

    		price = (k && price > 1000) ? Number(price / 1000).toFixed(this.currencyDecimals)+'K' : price;

    		return this.currencyPos === 'left' ? `${currencyCode} ${price}` : `${price} ${currencyCode}`;
    	}
    	
    	return free ? this.translation['Free'] : price;
    },
    itemIsFree: function()
    {
    	try 
    	{
    		var price = this.itemPrices[this.licenseId]['price'] || 0; 
    		
    		return price == 0 ? true : false;
    	}
    	catch(e)
    	{
    		return true;
    	}
    },
    priceConverted: function(price)
    {
    	if(price > 0)
    	{
    		return Number(price * this.exchangeRate).toFixed(this.currencyDecimals);
    	}
    	
    	return 0;
    },
    updatePrice: function(items)
    {
    	return $.post('/update_price', {items: items})
			.done(function(res)
			{
				return res.items;
			});
    },
    updateCartPrices: function()
    {
    	if(Object.keys(this.currencies).length)
			{
				this.updatePrice(this.cart).then(function(data)
				{
					app.cart = data.items;
				})
			}
    },
    getGuestDownloads: function()
    {
    	if(this.guestAccessToken.length)
    	{
    		$.post('/guest/downloads', {access_token: this.guestAccessToken})
    		.done(function(data)
    		{
    			if(data.hasOwnProperty('products'))
    			{
    				if(data.products.length)
    				{
    					app.guestItems = data.products;
    					app.keycodes = data.keycodes;

    					Vue.nextTick(function()
    					{
    						$('.ui.default.dropdown').dropdown({action: 'hide'})
    					})
    				}
    				else
    				{
	    				app.showUserMessage(app.__('No items found for the given token.'));
    				}

    				window.history.pushState("", "", `/guest?token=${app.guestAccessToken}`);
    			}
    		})
    		.fail(function(data)
    		{
    			app.showUserMessage(data.responseJSON.message);
    		})
    	}
    },
    downloadKey: function(itemId, itemSlug)
    {	
    	if(Object.keys(this.keycodes).indexOf(itemId.toString()) >= 0)
    	{
    		var blob = new Blob([this.keycodes[itemId]], {type: "text/plain;charset=utf-8"});

    		saveAs(blob, `${itemSlug}.txt`);
    	}
    },
    markUsersNotifAsRead: function()
    {
			$('#users-notif').remove();

			if(this.usersNotif.length)
			{
				Cookies.set('user_notif_read', this.usersNotif, {expires: 365});
				this.usersNotifRead = this.usersNotif;
			}
    },
    loadUserNotifsAsync: function()
    {
    	Push.Permission.request(() =>
			{
				setInterval(function()
	    	{
	    		$.post('/user_notifs')
		    	.done(function(notifications)
		    	{		    		
		    		if(notifications.length)
		    		{
							for(let i = 0; i < notifications.length; i++)
							{
								setTimeout(function timer() 
								{
									var userNotifsIds = JSON.parse(localStorage.getItem('userNotifsIds') || '[]');

									if(userNotifsIds.indexOf(notifications[i].id) < 0)
									{
										Push.create(app.appName, {
										    body: app.__(notifications[i].text, {"product_name": notifications[i].name}),
										    icon: `/storage/${notifications[i].for == '0' ? 'covers' : 'avatars'}/${notifications[i].image}`,
										    timeout: 4000,
										    onClick: function()
										    {
										    		$.post(props.routes.notifRead, {notif_id: notifications[i].id})
												    .done(function()
												    {
												      window.location.href = `/item/${notifications[i].slug}`;
												    })
										    }
										});

										localStorage.setItem('userNotifsIds', JSON.stringify(userNotifsIds.concat(notifications[i].id)));
									}
							  }, i * 5000);
							}
		    		}
		    	})
	    	}, 30000)
			})
    },
    acceptCookies: function()
    {
    	Cookies.set('cookies_accepted', true, {expires: 365});
    	this.cookiesAccepted = true;
    },
    setPrice: function(e)
    {
    	this.licenseId = e.target.value;

    	Vue.nextTick(()=>
    	{
    		var hasPromo = false;

    		if(this.itemPrices[this.licenseId]['promo_price'] !== null)
    		{
    			hasPromo = true;
    		}

    		if(this.itemPrices[this.licenseId]['has_promo_time'] == 1 && this.itemPrices[this.licenseId]['promotional_time'] == null)
    		{
    			hasPromo = false;
    		}

    		this.itemPromoPrice = hasPromo ? this.price(this.itemPrices[this.licenseId]['promo_price'], true) : null;
				this.itemHasPromo   = hasPromo ? true : false;

				this.product = Object.assign(this.product, {
					license_id: parseInt(this.licenseId),
					price: this.itemPrices[this.licenseId]['price']
				});
			})
    },
    sendEmailVerificationLink: function(userEmail)
    {
    	$('#main-dimmer').toggleClass('active', true);

    	$.post('/send_email_verification_link', {email: userEmail})
    	.done(function(data)
    	{
    		if(data.status)
    		{
    			app.showUserMessage(data.message);	
    		}
    	})
    	.always(function()
    	{
    		$('#main-dimmer').toggleClass('active', false);
    	})
    },
    removeRecentViewedItem: function(key)
	  {
	  	if(Object.keys(this.recentlyViewedItems).indexOf(key.toString()) >= 0)
	  	{
	  		var recentlyViewedItems = {};

	  		for(var k of Object.keys(this.recentlyViewedItems))
	  		{
	  			if(k != key)
	  			{
	  				recentlyViewedItems[k] = this.recentlyViewedItems[k];
	  			}
	  		}

	  		this.recentlyViewedItems = recentlyViewedItems;

	  		localStorage.setItem('recentlyViewedItems', JSON.stringify(this.recentlyViewedItems));
	  	}
	  }
	},
	watch: {
		itemHasPromo: function()
		{
			if(this.itemHasPromo)
			{
				Vue.nextTick(function()
				{
					startPromoCounter();
				})
			}
		}
	},
	created: function()
	{
		if(!this.trasactionMsg.length)
		{
			this.cart = this.cart.filter(function(item)
									{
										return item !== null
									});

			if(this.cart.length)
			{
				this.updateCartPrices();

				this.cartItems = this.cart.reduce(function(accumulator, cartItem)
				{
					return accumulator + 1;
				}, 0);
			}
		}
		else
		{
			this.cart = [];
			this.cartItems = 0;

			localStorage.removeItem('cartItems')
			localStorage.removeItem('cart');
		}

		this.parsedQueryString = queryString.parse(this.location.search);

		if(Object.keys(this.product).length)
		{
			this.licenseId = this.product.license_id;

			if(this.itemPrices.hasOwnProperty(this.licenseId))
			{
	    	Vue.nextTick(()=>
	    	{
	    		var hasPromo = false;

	    		if(this.itemPrices[this.licenseId]['promo_price'] !== null)
	    		{
	    			hasPromo = true;
	    		}

	    		if(this.itemPrices[this.licenseId]['has_promo_time'] == 1 && this.itemPrices[this.licenseId]['promotional_time'] == null)
	    		{
	    			hasPromo = false;
	    		}

	    		this.itemPromoPrice = hasPromo ? this.price(this.itemPrices[this.licenseId]['promo_price'], true) : null;
					this.itemHasPromo   = hasPromo ? true : false;

					this.product = Object.assign(this.product, {
						license_id: parseInt(this.licenseId),
						price: this.itemPrices[this.licenseId]['price']
					});
				})
			}
		}

		if(!Cookies.get('cookies_accepted'))
		{
			this.cookiesAccepted = false;
		}
	},
	mounted: function()
	{
		if(this.subscriptionId == null)
		{
			if(this.cartItems)
			{
				this.totalAmount = Number.parseFloat(this.cart.reduce(function(c, v){
															return c + v.price;
														}, 0)).toFixed(this.currencyDecimals);
			}	
		}
		else
		{
			this.totalAmount = Number.parseFloat(this.subscriptionPrice).toFixed(this.currencyDecimals);
		}
		

		if(!localStorage.hasOwnProperty('favorites'))
		{
			localStorage.setItem('favorites', '{}');
		}
		else
		{
			this.favorites = JSON.parse(localStorage.getItem('favorites'));
		}

		if(!localStorage.hasOwnProperty('recentlyViewedItems'))
		{
			localStorage.setItem('recentlyViewedItems', '{}');
		}
		else
		{
			this.recentlyViewedItems = JSON.parse(localStorage.getItem('recentlyViewedItems'));
		}

		if(this.route === 'home.product')
		{
			Vue.set(this.product, 'lastView', new Date().getTime()+`-${this.product.id}`);

			var ids = Object.keys(this.recentlyViewedItems).map(id => { return id.split('-')[1] });

			if(ids.indexOf(this.product.lastView.split('-')[1]) < 0)
			{
				var recentlyViewedItems = this.recentlyViewedItems;

				if(Object.keys(recentlyViewedItems).length === 13)
				{
					recentlyViewedItems = recentlyViewedItems.slice(1);
				}

				recentlyViewedItems[this.product.lastView] = this.product;

				this.recentlyViewedItems = recentlyViewedItems;
			}

			localStorage.setItem('recentlyViewedItems', JSON.stringify(this.recentlyViewedItems));
		}

		if(Cookies.get('user_notif_read'))
		{
			this.usersNotifRead = Cookies.get('user_notif_read');
		}

		this.loadUserNotifsAsync();
	}
});


function getObjectProp(obj, prop)
{
	if(obj === null)
		return null;

	if(obj.hasOwnProperty(prop))
		return obj[prop];

	return null;
}


function getObjProps(obj, props)
{
	var props_ = {};

	for(var prop of props)
	{
		props_[prop] = obj[prop];
	}

	return props_;
}


var formatTime = function (maxTime, currentTime) 
{
		let time = maxTime - currentTime;

    return [
        Math.floor((time % 3600) / 60), // minutes
        ('00' + Math.floor(time % 60)).slice(-2) // seconds
    ].join(':');
};


window.scrollBarWidth = (function()
{
	var outer = document.createElement('div');
	outer.style.visibility = 'hidden';
	outer.style.overflow = 'scroll';
	outer.style.msOverflowStyle = 'scrollbar';
	document.body.appendChild(outer);

	var inner = document.createElement('div');
	outer.appendChild(inner);
	
	var scrollbarWidth = (outer.offsetWidth - inner.offsetWidth);

	outer.parentNode.removeChild(outer);

	return scrollbarWidth;
})();


$.fn.isVisible = function(checkDisplay = false)
{
	var styles 	= getComputedStyle($(this)[0]);
	var visible = styles.visibility === 'visible';

	if(!checkDisplay)
	{
		return visible;
	}
	else
	{
		return visible && styles.display !== 'none';
	}
}

$.fn.replaceClass = function(oldClass, newClass)
{
	if(this.hasClass(oldClass))
		this.removeClass(oldClass).addClass(newClass);
};


function parseJson(jsonStr)
{
    var res;

    try
    {
      res = JSON.parse(jsonStr);
    }
    catch(e){}

    return res === undefined ? false : res;
}


function startPromoCounter()
{
	$('.promo-count').each(function()
	{
		var $this = $(this);
		var promoTime = $this.data('json');

		if(!(typeof promoTime === 'object' && promoTime !== null))
		{
			$this.hide();
			return;
		}

		var finalDate = new Intl.DateTimeFormat('en-US').format(new Date(promoTime.to));
		var day = {s: app.__('Day and'), p: app.__('Days and')};

	  $this.countdown(finalDate)
		.on('update.countdown', function(event)
		{
		  var format = '%H:%M:%S';

		  if(event.offset.totalDays > 0)
		  {
		    format = `%D ${String(event.offset.totalDays === 1 ? day.s : day.p).toLowerCase()} ` + format;
		  }

		  $this.find('span').text(event.strftime(format));
		})
		.on('finish.countdown', function(event)
		{
			var container = $this.closest('.card');

		  container.find('.promo-count').remove();
		  container.find('.promo-price').remove();
		  container.removeClass('in-promo');
		});
	})
}


window.debounce = function(func, wait, immediate) 
{
	var timeout;

	return function() 
	{
		var context = this, args = arguments;
		var later = function() 
		{
			timeout = null;
			if (!immediate) func.apply(context, args);
		};

		var callNow = immediate && !timeout;

		clearTimeout(timeout);

		timeout = setTimeout(later, wait);

		if (callNow) func.apply(context, args);
	};
};


$(()=>
{
	$(window).on('click', function(e)
	{
		if(!$(e.target).closest('#top-menu .dropdown.cart').length)
		{
			$('#top-menu .dropdown.cart .menu').replaceClass("visible", "hidden");
		}

		if(!$(e.target).closest('#top-menu .dropdown.notifications').length)
		{
			$('#top-menu .dropdown.notifications .menu').replaceClass("visible", "hidden");
		}

		if(!$(e.target).closest('.search-form').length)
		{
			app.searchResults = [];
		}
	})

	

	$(document).on('click', '#top-menu .dropdown.notifications .item:not(.all), #user .notifications .items a.item', 
  function()
  {
    var notifId = $(this).data('id');
    var _href = $(this).data('href');
    
    if(isNaN(parseInt(notifId)))
      return;

    $.post(props.routes.notifRead, {notif_id: notifId})
    .done(function()
    {
      location.href = _href;
    })
    .always(function()
    {
      if(location.href.includes('#'))
        location.reload()
    })
  })


	if($('.newest-item.popup').length)
  {
	  $('.home-items .wrapper.newest .ui.items .item')
		.popup({
	    inline     : false,
	    popup 			: $('.newest-item.popup'),
	    hoverable  : true,
	    position   : 'bottom left',
	    delay: {
	      show: 300
	    },
	    onShow: function(el)
	    {
	    	var item  = $(el).data('detail');
	    	var popup = $('.newest-item.popup');

	    	popup.find('img').attr('src', `/storage/covers/${item.cover}`);
	    	popup.find('.name').text(item.name);
	    	popup.find('.price').text(item.price > 0 ? `${app.price(item.price)}` : app.translation.Free);
	    }
	  })
  }


  $('#user .profile input[name="cashout_method"]').on('change', function()
	{
		let value = $(this).val().trim();
		
		if(!/^paypal_account|bank_account$/i.test(value))
		{
			return;
		}

		$(`#user .profile .option.${value}`).toggleClass('d-none', false)
		.siblings('.option').toggleClass('d-none', true)
	})
	
  $('#live-search input').on('keyup', debounce(function()
	{
			let q = $(this).val().trim();

			if(q.length)
			{
				$.post('/items/live_search', {q})
				.done(function(data)
				{
					app.liveSearchItems = data.products;
				})
			}
	}, 500))


  $(document).on('click', '.like .reactions a', function()
  {
  	var $this     = $(this);
  	var reaction  = $(this).data('reaction');
  	var groups  	= location.href.match(/.+\/item\/(?<id>\d+)\/.+/).groups;
  	var item_id   =  $(this).closest('.reactions').data('item_id');
  	var item_type = $(this).closest('.reactions').data('item_type');

  	$.post('/save_reaction', {reaction, product_id: groups.id || null, item_id, item_type})
  	.done(function(res)
  	{
  		if(res.status)
  		{
  			var reactionsHtml = [];

  			for(var i in res.reactions)
  			{
  				reactionsHtml.push('<span class="reaction" data-reaction="' + i + '" data-tooltip="' + res.reactions[i] + '" data-inverted="" style="background-image: url(\'/assets/images/reactions/' + i + '.png\')"></span>');
  			}  			

  			var savedReactions = $this.closest('.main-item').find('.extra').find('.saved-reactions');
  			var commentsCount = $this.closest('.main-item').find('.extra').find('.count');

  			if(!savedReactions.length)
  			{
  				if(commentsCount.length)
  				{
  					$this.closest('.main-item').find('.extra').html('<div class="saved-reactions" data-item_id="' + item_id + '" data-item_type="' + item_type + '"></div><div class="count">' + commentsCount.html() + '</div>')
  				}
  				else
  				{
  					$this.closest('.main-item')
  					.append('<div class="extra"><div class="saved-reactions" data-item_id="' + item_id + '" data-item_type="' + item_type + '"></div>')	
  				}  				
  				
  				$this.closest('.main-item').find('.saved-reactions').html(reactionsHtml.join(''));
  			}
  			else
  			{
  				savedReactions.html(reactionsHtml.join(''));
  			}
  			
  		}
  	})
  })

  $(document).on('click', '.saved-reactions .reaction', function()
  {
  	var reaction  = $(this).data('reaction');
  	var groups  	= location.href.match(/.+\/item\/(?<id>\d+)\/.+/).groups;
  	var item_id   =  $(this).closest('.saved-reactions').data('item_id');
  	var item_type = $(this).closest('.saved-reactions').data('item_type');

  	$.post('/get_reactions', {reaction, product_id: groups.id || null, item_id, item_type, users: true})
  	.done(function(res)
  	{
  		if(res)
  		{
  			app.usersReactions = res.reactions;
  			app.usersReaction = reaction;

  			Vue.nextTick(function()
  			{
  				$('#reactions').modal('show');
  			})
  		}
  	})
  })


  $(document).on('click', '#reactions .header a', function()
  {
  	app.usersReaction = $(this).data('reaction');
  })

	$('#top-menu .dropdown.cart.toggler').on('click', function()
	{
		$('#top-menu .dropdown.cart .menu').transition('drop');
	})

	$('#top-menu .dropdown.notifications.toggler').on('click', function()
	{
		$('#top-menu .dropdown.notifications .menu').transition('drop');
	})

	$('#items .column.left .tags label').on('click', function()
	{
		location.href = $(this).closest('a')[0].href;
	})

	$('.close.icon').on('click', function()
	{
		$(this).closest('.ui.modal').modal('hide');
	})


	$('.add-to-cart').on('click', function()
	{
		$(this, 'i').transition('tada');
	})
	

	$('#user-downloads tbody .image')
	.popup({
		inline     : true,
		hoverable  : true,
		position   : 'bottom left'
		})

	$('.marquee').marquee({
		duration: 20000,
		delayBeforeStart: 2000,
		gap: 0,
		startVisible: true,
		pauseOnHover: true,
		direction: props.direction === 'ltr' ? 'left' : 'right',
		duplicated: true
	});


	setTimeout(function()
	{
		$('#users-notif').css('visibility', 'visible');
	}, 100);

	$('.search-form .search.link').on('click', function()
	{
		$(this).closest('.search-form').submit();
	})

	$('.search-form').on('submit', function(e)
	{
		if(!$(this).find('input[name="q"]').val().trim().length)
		{
			e.preventDefault();
			return false;
		}
	})

	$('form.newsletter .plane.link').on('click', function()
	{
		$(this).closest('.form.newsletter').submit();
	})

	$('.form.newsletter').on('submit', function(e)
	{
		if(!/^(.+)@(.+)\.([a-z]+)$/.test($(this).find('input[name="email"]').val().trim()))
		{
			e.preventDefault();
			return false;
		}
	})

	

	$('.screenshot').on('click', function()
	{
		app.activeScreenshot = $(this).data('src');

		$('#screenshots').modal('show');
	})

	$(document).on('click', '.logout', function() {
		$('#logout-form').submit();
	})

	$('#item-r-side-toggler').on('click', function()
	{
		$('#item .r-side').transition('fly right');
	})


	$('#item .l-side .top.menu a.item:not(#item-r-side-toggler a)')
	.on('click', function()
	{
		$('#item .l-side .top.menu a.item').removeClass('active');
		$(this).toggleClass('active', true).transition('tada');

		$('#item .l-side .item > .column').hide()
		.siblings('.column.' + $(this).data('tab')).show();
	})


	$('.left-column-toggler').on('click', function()
	{
		$('#items .left.column').transition({
			animation: 'slide right'
		})
	})


	$('#mobile-menu .categories .items-wrapper').on('click', function()
	{
		$(this).toggleClass('active')
					.siblings('.items-wrapper').removeClass('active');
	})


	$('#items-menu>.item').on('click', function()
	{	
		$(this).toggleClass('active')
					.siblings('.item')
					.removeClass('active');
	})



	$('#item .card .header .link.angle.icon').on('click', function()
	{
		$(this).closest('.card').find('.content.body').toggle();
	})



	$(window).click(function(e)
	{
		if(!$(e.target).closest('#items-menu').length || $(e.target).closest('.search-item').length)
		{
			$('#items-menu>.item').removeClass('active');
		}
	})


	$('#user-profile .menu.unstackable .item').on('click', function()
	{
		var tab = $(this).data('tab');

		$(this).toggleClass('active', true)
					.siblings('.item').removeClass('active');
					
		$('#user-profile table.'+tab)
		.show()
		.siblings('.table').hide();
	})



	$('#user-profile input[name="user_avatar"]').on('change', function() {
		var file    = $(this)[0].files[0];
		var reader  = new FileReader();

		if(/^image\/(jpeg|jpg|ico|png|svg)$/.test(file.type))
		{
			reader.addEventListener("load", function() {
				$('#user-profile .user_avatar img').attr('src', reader.result);
			}, false);

			if(file)
			{
				reader.readAsDataURL(file);

				try
				{
					$('input[name="user_avatar_changed"]').prop('checked', true);
				}
				catch(err){}
			}
		}
		else
		{
			alert(qpp.__('File type not allowed!'));

			$(this).val('');
		}
	})

	$('.ui.checkbox').checkbox();
	$('.ui.checkbox.checked').checkbox('check');
	$('.ui.dropdown').dropdown();
	$('.ui.default.dropdown').dropdown({action: 'hide'})
	$('.ui.dropdown.nothing').dropdown({action: 'nothing'});

	$('.ui.rating.active').rating({
		onRate: function(rate)
		{
			$(this).siblings('input[name="rating"]').val(rate);
		}
	});

	$('.ui.rating.disabled').rating('disable');

	$('#recently-viewed-items .items').scrollLeft(-9999)

	if(app.route === 'home.product')
	{
		if(location.href.indexOf('#') >= 0)
		{
			var tab = $(`#item .l-side .top.menu .item[data-tab="${location.href.split('#')[1]}"]`);

			if(tab.length)
				tab[0].click();
		}
	}


	if(app.route === 'home.favorites')
	{
		if(Object.keys(app.favorites).length && app.currency.code !== app.userCurrency)
		{
				app.updatePrice(app.favorites).then(function(data)
				{
					app.favorites = data.items;
				})
		}
	}
	

	$('#support .segments .segment').on('click', function()
	{
		$('p', this).find('i').toggleClass($('div', this).is('visible') ? 'plus minus' : 'minus plus');
		$('div', this).slideToggle();
	})

	$('.message .close')
	.on('click', function() {
		$(this)
			.closest('.message')
			.transition('fade')
		;
	})

	$('#items .column.left .filter.cities .ui.dropdown input[type="hidden"]').on('change', debounce(function()
	{
			app.parsedQueryString.cities = $(this).val();
			
			Vue.nextTick(function()
			{
				location.href = queryString.stringifyUrl({url: app.location.href, query: app.parsedQueryString});
			})
	}, 3000))

	$('video')
	.hover(function()
	{
		$(this).prop('controls', true)[0].play();
	}, function()
	{
		$(this).prop('controls', false)[0].pause();
	})

	$('iframe.video')
	.each(function()
	{
		var src = $(this).attr('src');

		if(!/autoplay=(0|1)/i.test(src))
		{
			$(this).attr('src', src + (src.includes('?') ? ('&autoplay=0') : '?autoplay=0'));
		}
	})

	$('iframe.video')
	.mouseover(function(e)
	{
		var src = $(this).attr('src');

		$(this).attr('src', src.replace('autoplay=0', 'autoplay=1'));

		e.preventDefault();
	})
	.mouseleave(function(e)
	{
		var src = $(this).attr('src');

		$(this).attr('src', src.replace('autoplay=1', 'autoplay=0'));

		e.preventDefault();
	})


	$('.audio-container').each(function()
	{
		 let wSuffer = WaveSurfer.create({
		    container: $('.player .wave', this)[0],
		    backend: 'MediaElement',
		    responsive: true,
		    partialRender: true,
		    waveColor: '#D9DCFF',
		    progressColor: '#4353FF',
		    cursorColor: 'transparent',
		    barWidth: 2,
		    barRadius: 3,
		    cursorWidth: 1,
		    height: 45,
		    barGap: 2
		});

		wSuffer.once('ready', () => 
		{	
				$('audio', this).prop('loop', true);

				let setDuration = () =>
													{
														$('.player .duration', this).text(formatTime(wSuffer.getDuration(), wSuffer.getCurrentTime()));
													};

        $('.player .play', this).on('click', function() 
        {
        		$(this).toggleClass('visible', false).siblings('.pause').toggleClass('visible', true);

            wSuffer.play();
        });


        $('.player .pause', this).on('click', function() 
        {
        		$(this).toggleClass('visible', false).siblings('.play').toggleClass('visible', true);

            wSuffer.pause();

            setDuration()
        });

        $('.player .stop', this).on('click', function() 
        {
            wSuffer.stop();

            $(this).toggleClass('visible', false).siblings('.play').toggleClass('visible', true);

            setDuration()
        });


        $('.player .timeline .wave', this).on('click', () =>
        {
        		$(this).toggleClass('visible', false).siblings('.pause').toggleClass('visible', true);

            wSuffer.play();
        })


        wSuffer.on('seek', () =>
        {
					$('.player .pause', this).click();        	
        })


        wSuffer.on('audioprocess', function()
        {
        		setDuration()
        })

        wSuffer.on('finish', function()
        {
        	//$('.player i.pause', this).click()
        })

        setDuration();
    });

		try
		{
			let peaks = parseJson(window.peaks[$(this).data('id')]);

			wSuffer.load($(this).data('src'), peaks);
		}
    catch(e)
    {

    }
	})


	startPromoCounter();


	if(window.isMasonry)
	{
		$('.ui.cards.is_masonry').each(function()
		{
			$('.card', this).wrap('<div class="masonry-item">');

			resizeAllGridItems(this);

			if($('video', this).length)
			{
				let vidsCount  = $('video', this).length;
				let tries      = 5;

				let videosLoaded = setInterval(()=>
				{
					let loadedVids = 0;

					for(k = 0; k < vidsCount; k++)
					{
						loadedVids += $('video', this)[k].readyState === 4 ? 1 : 0;
					}

					if(loadedVids === vidsCount || tries === 0) 
					{						
						resizeAllGridItems(this);
						clearInterval(videosLoaded);
					}

					tries -= 1;
				}, 200)
			}

			$(window).resize(()=>
			{
				resizeAllGridItems(this);
			})
		})


		setTimeout(function()
		{
			$(window).resize();
		}, 5000)
	}

	$('[vhidden]').removeAttr('vhidden');
})