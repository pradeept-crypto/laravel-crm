<?php

namespace Webkul\Core;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Webkul\Core\Repositories\CoreConfigRepository;
use Webkul\Core\Repositories\CountryRepository;
use Webkul\Core\Repositories\CountryStateRepository;
use Webkul\Core\Traits\Sanitizer;

class Core
{
    use Sanitizer;

    /**
     * The Krayin version.
     *
     * @var string
     */
    const KRAYIN_VERSION = '2.2.5';

    /**
     * Create a new instance.
     *
     * @return void
     */
    public function __construct(
        protected CountryRepository $countryRepository,
        protected CoreConfigRepository $coreConfigRepository,
        protected CountryStateRepository $countryStateRepository
    ) {}

    /**
     * Get the version number of the Krayin.
     *
     * @return string
     */
    public function version()
    {
        return static::KRAYIN_VERSION;
    }

    /**
     * Retrieve all timezones.
     */
    public function timezones(): array
    {
        $timezones = [];

        foreach (timezone_identifiers_list() as $timezone) {
            $timezones[$timezone] = $timezone;
        }

        return $timezones;
    }

    /**
     * Retrieve all locales.
     */
    public function locales(): array
    {
        $options = [];

        foreach (config('app.available_locales') as $key => $title) {
            $options[] = [
                'title' => $title,
                'value' => $key,
            ];
        }

        return $options;
    }

    /**
     * Retrieve all countries.
     *
     * @return Collection
     */
    public function countries()
    {
        return $this->countryRepository->all();
    }

    /**
     * Returns country name by code.
     */
    public function country_name(string $code): string
    {
        $country = $this->countryRepository->findOneByField('code', $code);

        return $country ? $country->name : '';
    }

    /**
     * Returns state name by code.
     */
    public function state_name(string $code): string
    {
        $state = $this->countryStateRepository->findOneByField('code', $code);

        return $state ? $state->name : $code;
    }

    /**
     * Retrieve all country states.
     *
     * @return Collection
     */
    public function states(string $countryCode)
    {
        return $this->countryStateRepository->findByField('country_code', $countryCode);
    }

    /**
     * Retrieve all grouped states by country code.
     *
     * @return Collection
     */
    public function groupedStatesByCountries()
    {
        $collection = [];

        foreach ($this->countryStateRepository->all() as $state) {
            $collection[$state->country_code][] = $state->toArray();
        }

        return $collection;
    }

    /**
     * Retrieve all grouped states by country code.
     *
     * @return Collection
     */
    public function findStateByCountryCode($countryCode = null, $stateCode = null)
    {
        $collection = [];

        $collection = $this->countryStateRepository->findByField([
            'country_code' => $countryCode,
            'code' => $stateCode,
        ]);

        if (count($collection)) {
            return $collection->first();
        } else {
            return false;
        }
    }

    /**
     * Create singleton object through single facade.
     *
     * @param  string  $className
     * @return mixed
     */
    public function getSingletonInstance($className)
    {
        static $instances = [];

        if (array_key_exists($className, $instances)) {
            return $instances[$className];
        }

        return $instances[$className] = app($className);
    }

    /**
     * Format date
     *
     * @return string
     */
    public function formatDate($date, $format = 'd M Y h:iA')
    {
        return Carbon::parse($date)->format($format);
    }

    /**
     * Week range.
     *
     * @param  string  $date
     * @param  int  $day
     * @return string
     */
    public function xWeekRange($date, $day)
    {
        $ts = strtotime($date);

        if (! $day) {
            $start = (date('D', $ts) == 'Sun') ? $ts : strtotime('last sunday', $ts);

            return date('Y-m-d', $start);
        } else {
            $end = (date('D', $ts) == 'Sat') ? $ts : strtotime('next saturday', $ts);

            return date('Y-m-d', $end);
        }
    }

    /**
     * Return currency symbol from currency code.
     *
     * @param  string|null  $code
     * @return string
     */
    public function currencySymbol($code = null)
    {
        $code = $code ?: config('app.currency', 'INR');

        if (strtoupper((string) $code) === 'INR') {
            return '₹';
        }

        try {
            $formatter = new \NumberFormatter(app()->getLocale().'@currency='.$code, \NumberFormatter::CURRENCY);

            return $formatter->getSymbol(\NumberFormatter::CURRENCY_SYMBOL) ?: ($code === 'INR' ? '₹' : '$');
        } catch (\Throwable) {
            return $code === 'INR' ? '₹' : '$';
        }
    }

    /**
     * Format price with base currency symbol. This method also gives the ability to encode
     * the base currency symbol and its optional precision.
     *
     * @param  float|int|string|null  $price
     * @param  int  $precision
     * @return string
     */
    public function formatBasePrice($price, $precision = 2)
    {
        if (is_null($price) || $price === '') {
            $price = 0;
        }

        $currency = config('app.currency', 'INR');

        if (strtoupper((string) $currency) === 'INR') {
            return '₹'.number_format((float) $price, (int) $precision);
        }

        try {
            $formatter = new \NumberFormatter(app()->getLocale(), \NumberFormatter::CURRENCY);
            $formatter->setAttribute(\NumberFormatter::FRACTION_DIGITS, (int) $precision);

            return $formatter->formatCurrency((float) $price, $currency);
        } catch (\Throwable) {
            return '$'.number_format((float) $price, (int) $precision);
        }
    }

    /**
     * Get the config field.
     */
    public function getConfigField(string $fieldName): ?array
    {
        return system_config()->getConfigField($fieldName);
    }

    /**
     * Retrieve information for configuration.
     */
    public function getConfigData(string $field): mixed
    {
        return system_config()->getConfigData($field);
    }
}
