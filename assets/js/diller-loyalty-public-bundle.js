(function( $ ) {
	'use strict';

	window.Diller_Loyalty = window.Diller_Loyalty || {};
	window.Diller_Loyalty = $.extend({}, {
		phone: {
			preferredCountries: ["no", "se", "da"],
			selectedCountries: ["no", "se", "da"],
			allowedCountriesOption: "all", //all|specific|all_except
			defaultCountryCode: "+47",
			intlInputPluginEnabled: true
		},
		calendar: {
			locale: 'en', // Flatpicker locale
			placeholder: '', // To provide a hint for the user to type the correct date format
		},
		form: {
			enablePhoneLookup: true,
			enableEmailLookup: true,
			minEnrollmentAge: 0,
			fields: {},
			validationRules: {},
			validationRulesTexts: {
				agehigherthan: '',
				lessthandate: '',
				validdate: '',
				phonenumber: ''
			}
		},
		texts: {
			emailAlreadyExists: '',
			loginToMyAccount: '',
			otpCode: '',
			verifyOtpCode: '',
			sendVerificationCode: '',
			resendVerificationCode: '',
			removeCoupon: '',
			applyCoupon: ''
		},
		coupons: []
	}, window.Diller_Loyalty);

	console.log("Diller Loyalty: loaded", window.Diller_Loyalty);

	const htmlElem = document.getElementsByTagName("html");
	const currentLang = htmlElem ? htmlElem[0].lang : 'nb-NO'; // Use document's / WP language definition and not browsers definition
	const currentDateFormat = new Intl.DateTimeFormat(currentLang, { year: 'numeric', month: '2-digit', day: '2-digit' });
	const dateFormatLiteral = currentDateFormat.formatToParts().find(x => x.type === 'literal').value;

	const enablePhoneLookup = window.Diller_Loyalty.form.hasOwnProperty('enablePhoneLookup') ? window.Diller_Loyalty.form.enablePhoneLookup === true : false;
	const phoneInputField = window.Diller_Loyalty.form.fields.hasOwnProperty('phone_number') ? document.getElementById(window.Diller_Loyalty.form.fields.phone_number.id) : null;
	const ccInputField = window.Diller_Loyalty.form.fields.hasOwnProperty('phone_country_code') ? document.getElementById(window.Diller_Loyalty.form.fields.phone_country_code.id) : null;
	const defaultCountryFound = window.intlTelInputGlobals.getCountryData().filter(x => '+' + x.dialCode == window.Diller_Loyalty.phone.defaultCountryCode && x.priority == 0);

	let enableEmailLookup = window.Diller_Loyalty.form.hasOwnProperty('enableEmailLookup') ? window.Diller_Loyalty.form.enableEmailLookup === true : false;

	/**
	 * Function that expects a date string, tries to parse it based on server side / php date format (Y-m-d) or the current browser localization settings
	 * @param value string
	 * @returns {boolean|Date} False or Date object
	 */
	const tryParseDate = function(value){
		let result = false;
		console.log(`Parsing current raw date value: ${value}`);

		// 1rst parsing attempt
		// try to match server side / php format date formats Y-m-d
		const serverDateFormatRegExp = new RegExp(/(\d{4})[-\/\.](\d{2})[-\/\.](\d{2})/, 'i');
		let match = serverDateFormatRegExp.exec(value);
		if(match){
			result = new Date(parseInt(match[1], 10), parseInt(match[2], 10) - 1 , parseInt(match[3], 10));
			if(!isNaN(result.getTime())){
				console.log(`Parsing current date value: ${value} with regex: ${serverDateFormatRegExp}. Date value result: ${currentDateFormat.format(result)} | Current locale: ${currentLang}) | Date object result: `, result);
				return result;
			}
		}

		// 2nd parsing attempt
		const dateRegexParts = [], datePartsLength = { day: { length: 2, position: 0 }, month:  { length: 2, position: 1 }, year:  { length: 4, position: 2 } },
			formatParts = currentDateFormat.formatToParts().filter(x => x.type != 'literal'),
			dateFormatLiteral = currentDateFormat.formatToParts().find(x => x.type === 'literal').value;

		// Build the regex expression, based on position and length of date parts
		for (const index in formatParts) {
			const currPart = formatParts[index];
			datePartsLength[currPart.type].length = currPart.value.length;
			datePartsLength[currPart.type].position = index;
			dateRegexParts.push(`([0-9]{${currPart.value.length}})`);
		}

		const dateRegexExp = new RegExp(dateRegexParts.join(`\\${dateFormatLiteral}?`), 'i'); // Try to match date with separators
		match = dateRegexExp.exec(value);
		if(match){
			let day = 0, month = 0, year = 0;
			// Because RegExp named groups are not supported in all the browsers,
			// We need this extra code to find the right positioning for month, day and year in the regex match.
			for(let prop in datePartsLength){
				const groupIndex = parseInt(datePartsLength[prop].position)+1;
				if(prop === "day"){
					day = parseInt(match[groupIndex], 10);
				}
				if(prop === "month"){
					//NB: month is zero-based value that starts at 0, where January is 0 and February is 1 and so forth.
					month = parseInt(match[groupIndex], 10) - 1;
				}
				if(prop === "year"){
					year = parseInt(match[groupIndex], 10);
				}
			}

			result = new Date(year, month, day);
			if(!isNaN(result.getTime())){
				console.log(`Parsing current date value: ${value} with regex: ${dateRegexExp}. Date value result: ${currentDateFormat.format(result)} | Current locale: ${currentLang}) | Date object result: `, result);
				return result;
			}
		}
		return false;
	};

	/**
	 * If International Telephone Input plugin is instantiated, return the selected dial code, otherwise the default value
	 * configured in WP settings.
	 *
	 * @returns {string}
	 */
	const getCurrentDialCode = function(){
		return '+' + ((window.iti) ? window.iti.getSelectedCountryData().dialCode : defaultCountryFound[0].dialCode);
	}

	console.log("defaultCountryFound", defaultCountryFound);

	if(typeof(phoneInputField) != 'undefined' && phoneInputField != null){
		const allowMultipleCountries = window.Diller_Loyalty.phone.allowedCountriesOption == 'all' || window.Diller_Loyalty.phone.selectedCountries.length > 1;
		const phoneIntlTelInputOpts =  {
			allowDropdown: allowMultipleCountries,
			initialCountry: defaultCountryFound.length === 1 ? defaultCountryFound[0].iso2 : 'no',
			separateDialCode: true,
			formatOnDisplay: false,
			autoPlaceholder: "off",
			utilsScript: window.Diller_Loyalty.pluginUrl + "/assets/js/intl-tel-input/utils.js"
		};

		if(window.Diller_Loyalty.phone.allowedCountriesOption !== "all"){
			phoneIntlTelInputOpts.onlyCountries = window.Diller_Loyalty.phone.selectedCountries;
		}
		if((window.Diller_Loyalty.phone.preferredCountries || []).length > 0){
			phoneIntlTelInputOpts.preferredCountries = window.Diller_Loyalty.phone.preferredCountries;
		}

		// Initialize
		if(window.Diller_Loyalty.phone.intlInputPluginEnabled) {

			console.info("Initializing intlTelInput field with", phoneIntlTelInputOpts);

			// https://github.com/jackocnr/intl-tel-input#initialisation-options
			window.iti = window.intlTelInput(phoneInputField, phoneIntlTelInputOpts);

			//Initialize field
			if (ccInputField.value !== '' && phoneInputField.value !== '') {
				window.iti.setNumber(ccInputField.value.toString() + phoneInputField.value.toString());
			}

			phoneInputField.addEventListener("countrychange", updateCountryCodeFieldValue);
		}

		phoneInputField.addEventListener("blur", removeCountryCode);
		updateCountryCodeFieldValue();
		$(phoneInputField).trigger("change");

		function updateCountryCodeFieldValue(event) {
			const ccInputField = document.getElementById(window.Diller_Loyalty.form.fields.phone_country_code.id);
			const dialCode = getCurrentDialCode();
			$(ccInputField).val(dialCode);

			//Ensure the phone number field doesn't contain the dial code, as it's stored in the hidden field
			const phoneNumber = $(phoneInputField).val().replace(dialCode);
			console.log('updateCountryCodeFieldValue() called', dialCode, phoneNumber);
			if(phoneNumber.length >= 8 && phoneNumber !== 'undefined') {
			}
		}

		function removeCountryCode(event) {
			const dialCode = getCurrentDialCode();
			console.log('removeCountryCode() called', dialCode, event.target.value);
			if(event.target.value.match(/(\+|00)[0-9]{2,3}/i)) {
				event.target.value = event.target.value.replace(dialCode, "");
				if (window.Diller_Loyalty.form.id) {
					$(`#${window.Diller_Loyalty.form.id}`).validate().element(`#${event.target.id}`); // Force validation again for this field
				}
			}
		}
	}

	let lastCheckedPhoneNbr = '', lastCheckedEmail = '';
	const emailInputField = window.Diller_Loyalty.form.fields.hasOwnProperty('email') ? document.getElementById(window.Diller_Loyalty.form.fields.email.id) : null;
	const checkPhoneNumberAvailable = function(phoneNumber){
		return new Promise(function (resolve, reject) {

			console.info("Checking if phone number: " + phoneNumber + " is already taken...");

			// Bailout if it's invalid
			if (!phoneNumber.match(/^\d{8,10}$/)) resolve(false);

			const country = ((window.iti) ? window.iti.getSelectedCountryData().iso2 : defaultCountryFound[0].iso2).toUpperCase();
			const dialCode = getCurrentDialCode();

			$.ajax({
				type: "GET",
				url: Diller_Loyalty.restUrl + '/' + Diller_Loyalty.checkPhoneNumberEndpoint,
				data: {
					"country_iso2_code": country,
					"dial_code": dialCode,
					"phone_number": phoneNumber
				},
				beforeSend: function (xhr) {
					xhr.setRequestHeader("X-WP-Nonce", Diller_Loyalty.restNonce);
				},
				success: function (response) {
					console.info("Phone number " + phoneNumber + " is ", (response.success === true ? "available" : " unavailable"));
					resolve(response);
				},
				error: function (xhr, exception) {
					reject("Couldn't fetch data for the phone number provided");
				}
			});
		});
	}

	const checkEmailAvailable = function(email){
		return new Promise(function (resolve, reject) {

			console.info("Checking if email: "+ email +" is already taken...");

			$.ajax({
				type: "GET",
				url: Diller_Loyalty.restUrl + '/' + Diller_Loyalty.checkEmailEndpoint,
				data: {
					"email": email
				},
				beforeSend: function (xhr) {
					xhr.setRequestHeader("X-WP-Nonce", Diller_Loyalty.restNonce);
				},
				success: function (response) {
					console.info("email " + email + " is ", (response.success === true ? "available" : " unavailable"));
					resolve(response.success);
				},
				error: function (xhr, exception) {
					reject("Couldn't fetch data for the email provided");
				}
			});
		});
	}

	const fetchDetailsByPhoneNumber = function (phoneNumber = "") {
		phoneNumber = phoneNumber.replace(/\s|-|\+/g, '');

		console.info("Fetching persons details for phone number " + phoneNumber);

		const firstNameInputEl = document.getElementById(window.Diller_Loyalty.form.fields.first_name.id);
		const lastNameInputEl = document.getElementById(window.Diller_Loyalty.form.fields.last_name.id);
		const postalCodeInputEl = document.getElementById(window.Diller_Loyalty.form.fields.postal_code.id);
		const postalCityInputEl = document.getElementById(window.Diller_Loyalty.form.fields.postal_city.id);
		const addressInputEl = document.getElementById(window.Diller_Loyalty.form.fields.address.id);
		const alreadyFilled = (
			firstNameInputEl.value !== '' &&
			lastNameInputEl.value !== '' &&
			postalCodeInputEl.value !== '' &&
			postalCityInputEl.value !== '' &&
			addressInputEl.value !== ''
		);

		// Bailout if it's not a Norwegian number or it's invalid. We can only lookup NO numbers in 1881.no
		if ( alreadyFilled || getCurrentDialCode() !== '+47' || !phoneNumber.match(/^\d{8,10}$/) ) return;

		$.ajax({
			type: "GET",
			url: Diller_Loyalty.restUrl + '/' + Diller_Loyalty.checkPhoneNumberDetailsEndpoint,
			data: {
				"phone_number": phoneNumber
			},
			beforeSend: function (xhr) {
				xhr.setRequestHeader("X-WP-Nonce", Diller_Loyalty.restNonce);
			},
			success: function (response) {
				if (response.success === true && response.result) {
					console.info("Phone number " + phoneNumber + " looked up successfully.", response);

					//Fill in form data, but respect if people already filled in something.
					firstNameInputEl.value = response.result.first_name || firstNameInputEl.value;
					lastNameInputEl.value = response.result.last_name || lastNameInputEl.last_name;
					postalCodeInputEl.value = ('00' + (response.result.postal_code || postalCodeInputEl.value)).toString().slice(-4); // Left pad postal code. Eg. Oslo 0010, 0145
					postalCityInputEl.value = response.result.postal_city || postalCityInputEl.value;
					if (addressInputEl.value.length === 0) {
						addressInputEl.value = [response.result.street, response.result.house_number, response.result.entrance].join(' ').trim().replace(/\s{2,}/g,'');
					}
				}
				else{
					console.info("Phone number " + phoneNumber + ". No matches", response);
				}
			}
		});
	};


	/*PHONE NUMBER LOOKUP*/
	if(phoneInputField && enablePhoneLookup) {
		phoneInputField.addEventListener("blur", function (event) {
			const phoneNumber = phoneInputField.value = $(phoneInputField).val().replace(/\s/g, '');

			// return early, if we don't have a valid phone number
			if(!enablePhoneLookup || !isValidPhoneNumber(phoneNumber) || !$(phoneInputField).valid() || lastCheckedPhoneNbr === phoneNumber) return;

			// TODO: Implement enablePhoneLookup so that we don't call the api multiple times
			checkPhoneNumberAvailable(phoneNumber)
				.then(function (result) {
					// remove any previous validation errors
					$(`#${phoneInputField.id}-error`).remove();

					// Check if phone number is already taken...
					if(result.success !== true){
						displayAlertBanner(result.message, 'info');
						displayOtpFormFieldsStep1(result.data);
					}
					else{
						//... if not, fetch person details by phone number
						return fetchDetailsByPhoneNumber(phoneNumber);
					}
				})
				.catch(function (response) {
					console.log("checkPhoneNumberAvailable catch()", response);
				});

			lastCheckedPhoneNbr = phoneNumber;
		});
	}

	/*EMAIL VALIDATION AND AVAILABILITY*/
	if(emailInputField && enableEmailLookup) {
		emailInputField.addEventListener("blur", function (event) {
			const email = $(emailInputField).val();

			// Quit if invalid or the same email as before
			if(!enableEmailLookup || !$(emailInputField).valid() || lastCheckedEmail === email) return;

			checkEmailAvailable(email)
				.then(function (result) {

					// Check if email number is already taken
					if(result !== true){
						const errorSpanElem = $(`
							<span id="${emailInputField.id}-error" class="error field-invalid-feedback">
								${window.Diller_Loyalty.texts.emailAlreadyExists}<br/>${window.Diller_Loyalty.texts.loginToMyAccount}
							</span>`);
						$(emailInputField).removeClass("valid").addClass("invalid");
						console.log("error span email", $(`#${emailInputField.id}-error`));
						if($(`#${emailInputField.id}-error`).length > 0){
							console.log(`#${emailInputField.id}-error replace`);
							$(`#${emailInputField.id}-error`).replaceWith(errorSpanElem);
						}
						else{
							errorSpanElem.insertAfter($(`#${emailInputField.id}`));
						}
					}
					else {
						if ($(`#${emailInputField.id}-error`).length > 0) {
							$(`#${emailInputField.id}-error`).remove();
						}
					}

					lastCheckedEmail = email;
				})
				.catch(function (response) {
					console.log("checkEmailAvailable catch()", response);
				});
		});
	}

	//TODO: Consider replacing email validator rule with this one
	const isValidEmail = function(email= ''){
		let $0, url, isValid = false, emailPatternInput = /^[^@]{1,64}@[^@]{4,253}$/,
			emailPatternUrl = /^[^@]{1,64}@[a-z][a-z0-9\.-]{3,252}$/i;

		try{
			url = new URL('http://' + email);
			$0 = `${url.username}@${url.hostname}`;

			isValid = emailPatternInput.test(email);
			if(!isValid) throw 'invalid email pattern on input:' + email;

			isValid = emailPatternUrl.test($0);
			if(!isValid) throw 'invalid email pattern on url:' + $0;

			console.log(`email looks legit "${email}" checking url-parts: "${$0 === email ? '-SAME-':$0}"`);
		}
		catch(err){
			return false;
			//console.error(`probably not an email address: "${email}"`, err);
		}
		return isValid;
	}

	const isValidPhoneNumber = function(value){
		const phoneNumber = (value || '').replace(/\s/g, '');
		return value.length >= 8 && phoneNumber.match(/^\d{8,10}$/);
	};


	// Form Validations

	// Custom Validators
	$.validator.addMethod("phonenumber", function(value, element, params){
		console.log(`phonenumber validator called for field ${element.id} with value ${value}`);
		return this.optional(element) || isValidPhoneNumber(value);
	}, window.Diller_Loyalty.form.validationRulesTexts.phonenumber);

	$.validator.addMethod("validdate", function(value, element) {
		const parsedValue = tryParseDate(value);
		console.log(`validdate validator called for field ${element.id} with value ${value}`, parsedValue);
		return this.optional(element) || (parsedValue !== false && !isNaN(parsedValue.getTime()));
	}, `${window.Diller_Loyalty.form.validationRulesTexts.validdate} (${window.Diller_Loyalty.calendar.placeholder})`);

	$.validator.addMethod("lessthandate", function(value, element, params) {
		const currDateTime = tryParseDate(value),
			today = new Date();

		if(currDateTime === false || isNaN(currDateTime.getTime())){
			return this.optional(element) || false;
		}

		// Reset hours
		currDateTime.setHours(0, 0, 0, 0);
		today.setHours(0, 0, 0, 0);

		console.log(`lessthandate validator called for field ${element.id} with value ${value}`);

		return this.optional(element) || currDateTime <= today;
	}, window.Diller_Loyalty.form.validationRulesTexts.lessthandate);
	
	$.validator.addMethod("agehigherthan", function(value, element, params) {
		const dob = tryParseDate(value), today = new Date();
		if(dob === false || isNaN(dob.getTime())){
			return this.optional(element) || false;
		}

		let age = today.getFullYear() - dob.getFullYear(),
			m = today.getMonth() - dob.getMonth();
		if (m < 0 || (m === 0 && today.getDate() < dob.getDate())) {
			age--;
		}

		console.log(`age validator called for field ${element.id}. DOB: ${currentDateFormat.format(dob)} | Current age: ${age} higher than min age: ${window.Diller_Loyalty.form.minEnrollmentAge}`, age >= window.Diller_Loyalty.form.minEnrollmentAge);

		return this.optional(element) || (age >= window.Diller_Loyalty.form.minEnrollmentAge);
	}, window.Diller_Loyalty.form.validationRulesTexts.agehigherthan);

	// Default jQuery validate settings.
	// For extra validation methods: https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.19.3/jquery.validate.js#:~:text=required%3A%20%22This%20field%20is%20required.%22%2C
	$.validator.setDefaults({
		errorElement: "span",
		errorPlacement: function ( error, element ) {
			// Add the `field-invalid-feedback` class to the error element
			error.addClass("field-invalid-feedback");
			if (element.prop("type").match(/checkbox|radio/i)) {
				error.insertAfter(element.parents('.diller-form-group').children(":last"));
			}else if (element.prop("type") === "tel") {
				error.insertAfter(element.parent());
			}else{
				error.insertAfter(element);
			}
		},
		highlight: function (element, errorClass, validClass) {
			if ($(element).prop("type").match(/checkbox|radio/i)){
				$(element).parents('.diller-form-group').find('input:checkbox, input:radio').addClass("invalid").removeClass("valid");
			}
			else{
				$(element).addClass("invalid").removeClass("valid");
			}
		},
		unhighlight: function (element, errorClass, validClass) {
			if ($(element).prop("type").match(/checkbox|radio/i)){
				$(element).parents('.diller-form-group').find('input:checkbox, input:radio').addClass("valid").removeClass("invalid");
			}
			else{
				$(element).addClass("valid").removeClass("invalid");
			}
		}
	});

	console.log("loading rules from fields", window.Diller_Loyalty.form.validationRules);

	// Initialize jquery plugin on the form
	// Settings object: jQuery(form).validate().settings.ignore = "*";
	const $formValidator = $(`#${window.Diller_Loyalty.form.id}`).validate({
		rules: window.Diller_Loyalty.form.validationRules,
		submitHandler: function (form) {
			if ($(form).valid()) {
				// Disable submit button
				$(form).find('button[type="submit"]').prop("disabled", true);
				form.submit(); // Do not wrap it in jquery, or bad things will happen
			}
			else {
				// Enable submit button
				$(form).find('button[type="submit"]').prop("disabled", false);
			}
		}
		//invalidHandler: fires on click of the submit button only if/when the form is "invalid".
	});

	// Flatpickr to standardize date fields
	// https://flatpickr.js.org/options/
	if( $(`#${window.Diller_Loyalty.form.id}`).length > 0 ) {
		const dateFormatParts = currentDateFormat.formatToParts();
		let dateFormatString = ''; // Eg. Y-m-d

		// Builds PHP / Flatpicker's date format, based on the current document language.
		for (const part in dateFormatParts) {
			switch (dateFormatParts[part].type) {
				case 'month':
					dateFormatString += 'm';
					break;
				case 'literal':
					dateFormatString += dateFormatParts[part].value;
					break;
				case 'day':
					dateFormatString += 'd';
					break;
				case 'year':
					dateFormatString += 'Y';
					break;
				default:
					break;
			}
		}

		flatpickr(`#${window.Diller_Loyalty.form.id} input[type=date]`, {
			altInput: true,
			altFormat: dateFormatString, // UI date format = PHP date format
			dateFormat: 'Y-m-d', // Server side format = PHP date format
			allowInput: true, // Allows the user to enter a date directly into the input field
			locale: window.Diller_Loyalty.calendar.locale, // locale for this instance only
			parseDate: function (date, format) {
				const parsedDate = tryParseDate(date);
				console.log(`Flatpickr: parseDate called for raw value: ${date} using format: ${dateFormatString} | Result: `, parsedDate);
				return (parsedDate !== false && !isNaN(parsedDate.getTime())) ? parsedDate : new Date();
			},
			onOpen: function (selectedDates, dateStr, instance) {
				//$(instance.altInput).prop('readonly', true);
			},
			onClose: function (selectedDates, dateStr, instance) {
				$(instance.altInput).prop('readonly', false);
				$(instance.altInput).blur();
			},
			onReady: function (selectedDates, dateStr, instance) {
				$(instance.altInput).prop('readonly', false);
				$(instance.altInput).prop('placeholder', window.Diller_Loyalty.calendar.placeholder);

				// Flatpickr replaces the original input field with an hidden input, and create its own text input
				// We copy all the relevant attributes from the original input (now the hidden) to the new input so that we can handle validations
				const field = $(instance.altInput).prev("input[type=hidden]"),
					fieldId = field.prop('id'),
					fieldName = field.prop('name');

				console.log("Flatpickr ready!", field, fieldId);
				field.prop('id', `hidden_${fieldId}`);
				field.prop('name', `hidden_${fieldName}`);

				$(instance.altInput).prop('id', fieldId)
				$(instance.altInput).prop('name', fieldName);
			}
		});
	}


	// Woocommerce Coupons
	window.Diller_Loyalty.coupons = window.Diller_Loyalty.coupons || [];
	$('.woocommerce-cart button[data-diller-coupon]').each(function(i, btnElem){
		const availableCoupon = $(btnElem).data("diller-coupon");
		if(availableCoupon){
			window.Diller_Loyalty.coupons.push(availableCoupon);
		}
	});

	console.info(`Diller - available coupons`, window.Diller_Loyalty.coupons);

	const toggleCouponState = function(couponCode, state){
		couponCode = couponCode.toLowerCase(); // WC always lowercases coupon codes
		const couponBtn = $(`button[data-diller-coupon="${couponCode}" i]`); // i = case insensitive
		if(!couponBtn || couponBtn.length === 0) return;

		if(state !== "enable"){
			//couponBtn.prop('disabled', true);
			couponBtn.data('diller-action', 'remove-coupon');
			couponBtn.html(window.Diller_Loyalty.texts.removeCoupon);
			couponBtn.parents('.diller-coupon').addClass('diller-coupon--grayout');
			console.info(`Cart - Disabling coupon: ${couponCode}`);
		}
		else{
			//couponBtn.prop('disabled', false);
			couponBtn.data('diller-action', 'apply-coupon');
			couponBtn.html(window.Diller_Loyalty.texts.applyCoupon);
			couponBtn.parents('.diller-coupon').removeClass('diller-coupon--grayout');
			console.info(`Cart - Enabling coupon: ${couponCode}`);
		}
	};

	//Ensure already applied coupons are grayed out, on page reloaded.
	$(`.cart_totals a[data-coupon]`).each(function(i, elem){
		toggleCouponState($(elem).data("coupon"), 'disable');
	});

	//Events
	$("button[data-diller-coupon]").click(function(){

		// Block buttons, because sometimes applying the coupon can take some time to process and we want to prevent overdoing it.
		$("button[data-diller-coupon]").prop("disabled", true);

		const coupon = $(this).data("diller-coupon").toLowerCase();
		const action = $(this).data("diller-action");

		console.log(`coupon clicked: `, coupon, action);

		if(coupon && action === "apply-coupon"){
			$("#coupon_code").val(coupon);
			$("[name='apply_coupon']").trigger("click");
		}else if(coupon && action === "remove-coupon"){
			window.location = $(`a.woocommerce-remove-coupon[data-coupon=${coupon}]`).attr("href");
		}
	});

	$("body").on('applied_coupon', function(event, coupon){

		// Check if there were errors applying the coupon and if so, don't change coupons state
		const $wcError = $( '.woocommerce-error' );
		const foundInvalidCoupon = window.Diller_Loyalty.coupons.find(x => $wcError.length > 0 && x.match(new RegExp(coupon, 'i')));
		if(!foundInvalidCoupon) {
			for (const couponsKey in window.Diller_Loyalty.coupons) {
				const currCouponCode = window.Diller_Loyalty.coupons[couponsKey].toString(),
					newState = currCouponCode.match(new RegExp(coupon, 'i')) ? 'disable' : 'enable';

				toggleCouponState(currCouponCode, newState);
			}
		}

		const intervalId = setInterval(function(){
			if(false === $(".woocommerce-cart-form").hasClass("processing")){
				clearInterval(intervalId);
				$("button[data-diller-coupon]").prop("disabled", false);
				console.info(`Cart: Coupon applied: ${coupon} | Found errors: ${$wcError.length > 0}`);
			}
		}, 100);
	});

	$("body").on('removed_coupon', function(event, coupon){
		toggleCouponState(coupon, 'enable');
		console.info(`Cart: Removed coupon: ${coupon}`);
	});

	const displayAlertBanner = function(message, category = 'info'){
		let $formAlertElem = $(`#${Diller_Loyalty.form.id}-alert-box`);
		if($formAlertElem.length === 0){
			$formAlertElem = $(`<div id="${Diller_Loyalty.form.id}-alert-box" class=""></div>`);
			$(`#${Diller_Loyalty.form.id}`).prepend($formAlertElem);
		}
		$formAlertElem.hide();
		$formAlertElem.removeAttr('class');
		$formAlertElem.addClass(`diller-alert diller-alert--${category} diller-w-100`);

		// Note 2 self: without the delay() here some content would not render properly
		$formAlertElem.delay(50).queue(function() {
			$(this).html(message).show().dequeue();
		});
	};


	// OTP CODE INTEGRATION
	const otpFormFields = {
		"country_iso2_code": "",
		"phone_number": "",
		"phone_country_code": "",
		"membership_consent_accepted": "No",
		"purchase_history_consent_accepted": "No",
		"email": "",
		"otp_code": ""
	};

	const displayOtpFormFieldsStep1 = function(userData){
		const activeValidationRules = jQuery(`#${Diller_Loyalty.form.id}`).validate().settings.rules;
		const fieldsToValidate = ['phone_number'];
		const selectorFieldsRemove = [
			`#${Diller_Loyalty.form.id} input:not([type=hidden],[name=membership_consent_accepted],input[name=purchase_history_consent_accepted],[name=phone_number],[name=email])`,
			`#${Diller_Loyalty.form.id} select`
		];

		// This ensures we use the same css styles everywhere.
		const submitBtnCssClasses = jQuery(`#${Diller_Loyalty.form.id} > button[type=submit]`).attr("class");
		const $newFields = jQuery(`
			<div class="diller-form-group">
				<input type="hidden" name="token" value="${userData.token}" />
				<button id="otp-submit-btn" class="${submitBtnCssClasses}" type="button" data-diller-action="request-otp-code">
					${Diller_Loyalty.texts.sendVerificationCode}
				</button>
			</div>`);

		// Hide email field for later and removed the other unwanted field for this step
		if(userData.validEmail === true) {
			jQuery(`#${Diller_Loyalty.form.id} input[name=email]`).parents('.diller-form-group').remove();
		}else{
			fieldsToValidate.push("email");
		}

		if(userData.consentAccepted === true) {
			jQuery(`#${Diller_Loyalty.form.id} input[name=membership_consent_accepted]`).parents('.diller-form-group').remove();
			jQuery(`#${Diller_Loyalty.form.id} input[name=purchase_history_consent_accepted]`).parents('.diller-form-group').remove();
		}else{
			fieldsToValidate.push("membership_consent_accepted");
		}

		// remove other unnecessary form elements
		jQuery(selectorFieldsRemove.join(", ")).parents('.diller-form-group').remove();

		// Prevent normal form submit by removing the submit button
		jQuery(`#${Diller_Loyalty.form.id} > button[type=submit]`).remove();

		jQuery($newFields).appendTo(`#${Diller_Loyalty.form.id}`);

		// Disable phone field to prevent changing the number
		jQuery(phoneInputField).prop("disabled", true);

		// Remove jQuery validate, unnecessary rules
		Object.keys(activeValidationRules).forEach((rule) => fieldsToValidate.includes(rule) || delete activeValidationRules[rule]);

		// Disable email lookup for this step
		enableEmailLookup = false;
	}

	const displayOtpFormFieldsStep2 = function(){
		//debugger;
		const fieldUniqSuffix = Math.random().toString(36).replace(/^0\./g, '');
		const $otpCodeElem = jQuery(`
			<div class="diller-form-group">
				<label for="otp_code-${fieldUniqSuffix}">${Diller_Loyalty.texts.otpCode}</label>
				<input type="text" value="" placeholder="" id="otp_code-${fieldUniqSuffix}" class="diller-form-control" name="otp_code">
				<a id="diller-resend-otp-code" href="#">${Diller_Loyalty.texts.resendVerificationCode}</a>
			</div>`);

		// Hide previous fields for simplicity
		jQuery(`#${Diller_Loyalty.form.id}`)
			.find(`input[name=membership_consent_accepted],input[name=purchase_history_consent_accepted],input[name=email]`)
			.parents(".diller-form-group")
			.hide();

		// Update action button
		const $submitBtn = jQuery(`#${Diller_Loyalty.form.id} button[id=otp-submit-btn]`);
		$submitBtn.html(Diller_Loyalty.texts.verifyOtpCode);

		const $otpCodeField = $(`#${Diller_Loyalty.form.id} input[name=otp_code]`);
		if($otpCodeField.length == 0) {
			jQuery($otpCodeElem).insertBefore($submitBtn);

			$otpCodeField.keydown(function (event) {
				if ((event.keyCode || event.which) === 13) {
					handleOtpFormSubmission();
				}
			});

			// Add field to jquery validate rules collection
			Object.assign(jQuery(`#${Diller_Loyalty.form.id}`).validate().settings.rules, {
				"otp_code" : {
					required: true,
					minlength: 5,
					digits: true
				}
			});
		}

		console.log("Current rules:", jQuery(`#${Diller_Loyalty.form.id}`).validate().settings.rules);
	}

	const performOtpCodeRequest = function(data){
		return new Promise(function (resolve, reject) {
			console.info("Sending sms to phone number: ", data);
			$.ajax({
				type: "POST",
				url: Diller_Loyalty.restUrl + '/' + Diller_Loyalty.sendOtpLoginCodeEndpoint,
				data: data,
				beforeSend: function (xhr) {
					xhr.setRequestHeader("X-WP-Nonce", Diller_Loyalty.restNonce);
				},
				success: function (response) {
					console.info((response.success === true ? "SMS Code Sent" : " Couldn't SMS Code"));
					if(response.success === true){
						resolve(response.message);
					}else{
						reject(response.message || '');
					}
				},
				error: function (xhr, exception) {
					const response = xhr.responseJSON || { "message": "Couldn't send SMS code to the phone number provided" };
					reject(response.message);
				}
			});
		});
	}

	const performOtpCodeValidation = function(data){
		return new Promise(function (resolve, reject) {
			console.info("Validating sms code: ", data);
			$.ajax({
				type: "POST",
				url: Diller_Loyalty.restUrl + '/' + Diller_Loyalty.validateOtpLoginCodeEndpoint,
				data: data,
				beforeSend: function (xhr) {
					xhr.setRequestHeader("X-WP-Nonce", Diller_Loyalty.restNonce);
				},
				success: function (response) {
					console.info((response.success === true ? "SMS code validated" : "Couldn't validate the SMS code"));
					if(response.success === true){
						resolve(response);
					}else{
						reject(response);
					}
				},
				error: function (xhr, exception) {
					reject("Couldn't validate the SMS code");
				}
			});
		});
	}

	const handleOtpFormSubmission = function() {
		const formData = getOtpFormData();

		if($(this).data("diller-action") === "request-otp-code" && validateOtpFormData()){
			return handleOtpCodeRequest(formData);
		}
		if($(this).data("diller-action") === "validate-otp-code" && validateOtpFormData()){
			return handleOtpCodeValidation(formData);
		}
	};

	const handleOtpCodeRequest = function(formData) {

		const $btnElem = jQuery(`#${Diller_Loyalty.form.id} button[id=otp-submit-btn]`);

		console.log(`handleOtpCodeRequest called by `, formData);

		// Disable button to prevent replaying the request
		$btnElem.prop("disabled", true);

		// Ask server to send the OTP code
		performOtpCodeRequest(formData).then(function(result){
			console.log("code sent", result);
			$btnElem.data("diller-action", "validate-otp-code");
			displayAlertBanner(result, 'info');
			displayOtpFormFieldsStep2();
		}).catch(function(result){
			console.log("error", result);
			displayAlertBanner(result, 'danger');
		}).finally(function(){
			$btnElem.prop("disabled", false);
		});
	};

	const handleOtpCodeValidation = function() {
		if(!validateOtpFormData()) return;

		const $btnElem = jQuery(`#${Diller_Loyalty.form.id} button[id=otp-submit-btn]`);
		const formData = getOtpFormData();
		console.log(`handleOtpCodeValidation called by `, formData);

		// Disable button to prevent replaying the request
		$btnElem.prop("disabled", true);

		performOtpCodeValidation(formData).then(function(result){
			console.log("SMS Code validated", result);
			displayAlertBanner(result.message, 'success');
			$(`#${Diller_Loyalty.form.id} :not(.diller-alert)`).remove();
		})
		.catch(function(result){
			displayAlertBanner(result.message, 'danger');
			if(result.data){
				Object.keys(result.data).forEach(function (fieldName){
					const $field = $(`#${Diller_Loyalty.form.id} input[name=${fieldName}]`);
					if($field.length > 0) {
						$(`#${Diller_Loyalty.form.id} input[name=${fieldName}]`).parents('.diller-form-group').show();
					}
				});
				$formValidator.showErrors(result.data);
				console.log("Email already exists", result);
			}
		}).finally(function(){
			$(phoneInputField).prop("disabled", false);
			$btnElem.prop("disabled", false);
		});
	};

	const getOtpFormData = function() {
		otpFormFields.phone_number = phoneInputField.value = $(phoneInputField).val().replace(/\s/g, '');
		otpFormFields.country_iso2_code = ((window.iti) ? window.iti.getSelectedCountryData().iso2 : defaultCountryFound[0].iso2).toUpperCase();

		$(`#${Diller_Loyalty.form.id}`).serializeArray().reduce(function(prevValue, currValue){
			if(currValue.value !== "") {
				otpFormFields[currValue.name] = currValue.value.replace(/\s/g, '');
			}
			return otpFormFields;
		}, otpFormFields);

		return otpFormFields;
	};

	/**
	 * Validate OTP form fields and allows a callback as argument, to exclude fields from the validation.
	 * @param filterPredicate
	 * @returns {boolean}
	 */
	const validateOtpFormData = function(filterPredicate) {
		const formData = getOtpFormData();

		//debugger;

		if(formData === false) return;

		if(typeof filterPredicate != "function"){
			filterPredicate = field => true;
		}

		const invalidFields = Object.keys(formData)
			.filter(filterPredicate)
			.filter(fieldName => jQuery(`input[name=${fieldName}]`).length > 0 && jQuery(`input[name=${fieldName}]`).valid() === false);

		return invalidFields.length == 0;
	};


	$(document).on("click", "a#diller-resend-otp-code" , function(event) {
		event.preventDefault();

		// Skip OTP code fields from validation, if resending code
		if(!validateOtpFormData(key => key != "otp_code")) return;

		return handleOtpCodeRequest(getOtpFormData());
	});

	// Handle OTP form submission
	$(document).on("click", "button[id=otp-submit-btn]" , handleOtpFormSubmission);

})( jQuery );

//IMPROVE: Encapsulate methods and members via function + object notation

function DillerModal(modalParams) {
	const me = this;
	const _modalElem = document.getElementById(modalParams.id);

	me.showModal = function () {

		_modalElem.classList.add('diller-modal--open');

		// Dispatch the event.
		_modalElem.dispatchEvent(new Event('shown', {
			bubbles: false,
			cancelable: true
		}));
	};

	me.closeModal = function () {
		_modalElem.classList.remove('diller-modal--open');

		// Dispatch the event.
		_modalElem.dispatchEvent(new Event('closed', {
			bubbles: false,
			cancelable: true,
		}));
	};

	me.setContent = function (content) {
		console.log(content);
		jQuery(_modalElem).find(".diller-modal-title").html(content.title);
		jQuery(_modalElem).find(".diller-modal-body").html(content.body);

		//Buttons
		jQuery(_modalElem).find(".diller-modal-footer").html('');
		for(const index in content.buttons){
			const button = content.buttons[index];
			const clickCallback = ((button.click_callback || '').length > 0) ? ' onclick="' + button.click_callback + '" ' : '';
			jQuery(_modalElem).find(".diller-modal-footer")
				.append('<button class="' + button.class + '" ' + clickCallback + ' >' + button.text + '</button>');
		}
	};

	jQuery(_modalElem).find("[data-dismiss]").click(function(){
		_modalElem.dispatchEvent(new Event('closing', {
			bubbles: false,
			cancelable: true
		}));
	});

	return {
		show: me.showModal,
		hide: me.closeModal,
		setContent: me.setContent,
		on: function (event, callback) {
			_modalElem.addEventListener(event, callback, false);
		}
	}
}

//Check if we have a modal dialog to setup
if(window.Diller_Loyalty.modal !== undefined) {
	const membCheckboxElem = document.getElementById(window.Diller_Loyalty.modal.trigger),
		modalElem = document.getElementById(window.Diller_Loyalty.modal.id);

	if (membCheckboxElem && modalElem) {
		const modalInstance = new DillerModal(window.Diller_Loyalty.modal);
		window.Diller_Loyalty.getModalInstance = function () {
			return modalInstance;
		};

		// Handle unsubscribe checkbox click
		membCheckboxElem.addEventListener('change', function (e) {
			console.log("handleMembershipCheckboxChange triggered", membCheckboxElem.checked, membCheckboxElem.dataset.unsubscribe);

			// User want to unsubscribe, show confirmation dialog
			if (!membCheckboxElem.checked && membCheckboxElem.dataset.unsubscribe !== 'Yes') {
				window.Diller_Loyalty.getModalInstance().setContent(window.Diller_Loyalty.modal);
				window.Diller_Loyalty.getModalInstance().show();
			} else if (membCheckboxElem.checked) {
				// Restore
				membCheckboxElem.removeAttribute('data-unsubscribe');

				// Restore jQuery validation for this field
				jQuery(membCheckboxElem).rules('add', 'required');
			}
		});

		// Handle modal confirm click
		window.Diller_Loyalty.handleUnsubscribeBtnClick = function (e) {
			membCheckboxElem.dataset.unsubscribe = 'Yes'; // data-unsubscribe="Yes"

			// Remove jQuery validation for this field
			jQuery(membCheckboxElem).rules('remove', 'required');

			window.Diller_Loyalty.getModalInstance().hide();

			//TODO: Change submit button text from "Save my preferences" to "Unsubscribe" ??
		};

		// Handle modal cancel / X click
		const cancelUnsuscribeCallback = function (e) {
			console.log("handleUnsubscribeCancelBtnClick() called");
			document.getElementById(window.Diller_Loyalty.modal.trigger).checked = true;
			window.Diller_Loyalty.getModalInstance().hide();
		};
		window.Diller_Loyalty.handleUnsubscribeCancelBtnClick = cancelUnsuscribeCallback;
		window.Diller_Loyalty.getModalInstance().on('closing', cancelUnsuscribeCallback);
	}
}
