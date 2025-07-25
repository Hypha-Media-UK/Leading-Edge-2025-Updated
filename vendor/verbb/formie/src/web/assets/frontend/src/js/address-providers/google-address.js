import { getAjaxClient } from '../utils/utils';
import { FormieAddressProvider } from './address-provider';

export class FormieGoogleAddress extends FormieAddressProvider {
    constructor(settings = {}) {
        super(settings);

        this.$form = settings.$form;
        this.form = this.$form.form;
        this.$field = settings.$field;
        this.$input = this.$field.querySelector('[data-autocomplete]');
        this.scriptId = 'FORMIE_GOOGLE_ADDRESS_SCRIPT';

        this.appId = settings.appId;
        this.apiKey = settings.apiKey;
        this.geocodingApiKey = settings.geocodingApiKey || settings.apiKey;
        this.options = settings.options;

        // Keep track of how many times we try to load.
        this.retryTimes = 0;
        this.maxRetryTimes = 150;
        this.waitTimeout = 200;

        if (!this.$input) {
            console.error('Unable to find input `[data-autocomplete]`.');

            return;
        }

        this.initScript();
    }

    initScript() {
        // Prevent the script from loading multiple times (which throw warnings anyway)
        if (!document.getElementById(this.scriptId)) {
            const script = document.createElement('script');
            script.src = `https://maps.googleapis.com/maps/api/js?key=${this.apiKey}&loading=async&libraries=places`;
            script.defer = true;
            script.async = true;
            script.id = this.scriptId;
            script.onload = () => {
                // Just in case there's a small delay in initializing the scripts after loaded
                this.waitForLoad();
            };

            document.body.appendChild(script);
        } else {
            // Script already present, but might not be loaded yet...
            this.waitForLoad();
        }
    }

    waitForLoad() {
        // Prevent running forever
        if (this.retryTimes > this.maxRetryTimes) {
            console.error(`Unable to load Google API after ${this.retryTimes} times.`);
            return;
        }

        // Ensure that Google places is ready
        if (typeof google === 'undefined' || typeof google.maps === 'undefined' || typeof google.maps.places === 'undefined') {
            this.retryTimes += 1;

            setTimeout(this.waitForLoad.bind(this), this.waitTimeout);
        } else {
            this.initAutocomplete();
        }
    }

    initAutocomplete() {
        const options = { types: ['geocode'], ...this.options };

        // Init the autocomplete web componenent, which we have limited style control over...
        const autocomplete = new google.maps.places.PlaceAutocompleteElement(options);
        autocomplete.style.height = window.getComputedStyle(this.$input).height;
        autocomplete.style.boxSizing = 'border-box';

        // Find or create a wrapper div around the input to mount the autocomplete to.
        let wrapper = this.$input.parentElement;

        if (!wrapper || !wrapper.classList.contains('fui-autocomplete-wrapper')) {
            wrapper = document.createElement('div');
            wrapper.classList.add('fui-autocomplete-wrapper');

            this.$input.parentNode.insertBefore(wrapper, this.$input);
            wrapper.appendChild(this.$input);
        }

        // Replace the input with the Web Component
        wrapper.replaceChild(autocomplete, this.$input);

        // Mirror selected value into the original input (needed to keep input compatible with Formie / server-side)
        const hiddenInput = this.$input;
        hiddenInput.type = 'hidden';
        hiddenInput.name = this.$input.name;
        wrapper.appendChild(hiddenInput);

        autocomplete.addEventListener('gmp-select', async({ placePrediction }) => {
            const place = placePrediction.toPlace();
            await place.fetchFields({ fields: ['addressComponents'] });

            if (!place.addressComponents) { return; }

            this.setAddressValues(place.addressComponents);

            const populateAddressEvent = new CustomEvent('populateAddress', {
                bubbles: true,
                detail: {
                    addressProvider: this,
                    addressComponents: place.addressComponents,
                },
            });

            this.$field.dispatchEvent(populateAddressEvent);
        });
    }

    setAddressValues(address) {
        const formData = {};
        const componentMap = this.componentMap();

        // Sort out the data from Google so its easier to manage
        for (let i = 0; i < address.length; i++) {
            const [addressType] = address[i].types;

            if (componentMap[addressType]) {
                formData[addressType] = address[i][componentMap[addressType]];
            }
        }

        if (formData.street_number && formData.route) {
            let street = `${formData.street_number} ${formData.route}`;

            if (formData.subpremise) {
                street = `${formData.subpremise}/${street}`;
            }

            this.setFieldValue('[data-address1]', street);
        }

        this.setFieldValue('[data-city]', formData.locality, formData.postal_town);
        this.setFieldValue('[data-zip]', formData.postal_code);
        this.setFieldValue('[data-state]', formData.administrative_area_level_1);
        this.setFieldValue('[data-country]', formData.country);
    }

    onCurrentLocation(position) {
        const { latitude, longitude } = position.coords;

        const xhr = getAjaxClient(this.$form, 'POST', window.location.href, true);
        xhr.timeout = 10 * 1000;

        xhr.ontimeout = () => {
            console.log('The request timed out.');
        };

        xhr.onerror = (e) => {
            console.log('The request encountered a network error. Please try again.');
        };

        xhr.onload = () => {
            this.onEndFetchLocation();

            if (xhr.status >= 200 && xhr.status < 300) {
                try {
                    const response = JSON.parse(xhr.responseText);

                    if (response && response.results && response.results[0] && response.results[0].address_components) {
                        this.setAddressValues(response.results[0].address_components);
                    }

                    if (response.error_message || response.error) {
                        console.log(response);
                    }
                } catch (e) {
                    console.log(e);
                }
            } else {
                console.log(`${xhr.status}: ${xhr.statusText}`);
            }
        };

        // Use our own proxy to get around lack of support from Google Places and restricted API keys
        const formData = new FormData();
        formData.append('action', 'formie/address/google-places-geocode');
        formData.append('latlng', `${latitude},${longitude}`);
        formData.append('key', this.geocodingApiKey);

        xhr.send(formData);
    }

    componentMap() {
        /* eslint-disable camelcase */
        return {
            subpremise: 'shortText',
            street_number: 'shortText',
            route: 'longText',
            postal_town: 'longText',
            locality: 'longText',
            administrative_area_level_1: 'shortText',
            country: 'shortText',
            postal_code: 'shortText',
        };
        /* eslint-enable camelcase */
    }

    setFieldValue(selector, value, fallback) {
        if (this.$field.querySelector(selector)) {
            this.$field.querySelector(selector).value = value || fallback || '';
        }
    }
}

window.FormieGoogleAddress = FormieGoogleAddress;
