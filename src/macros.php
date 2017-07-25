<?php

use Laravel\Scout\Builder;


if (! Builder::hasMacro('count')) {
    /**
     * Return the total amount of results for the current query.
     *
     * @return int Number of results
     */
    Builder::macro('count', function () {
        $raw = $this->engine()->search($this);

        return (int) $raw['nbHits'];
    });
}

if (! Builder::hasMacro('aroundLatLng')) {
    /**
     * Search for entries around a given location.
     *
     * @see https://www.algolia.com/doc/guides/geo-search/geo-search-overview/
     *
     * @param float $lat Latitude of the center
     * @param float $lng Longitude of the center
     *
     * @return Laravel\Scout\Builder
     */
    Builder::macro('aroundLatLng', function ($lat, $lng, $radius=null, $service=null, $employees_sizes) {
        $callback = $this->callback;
        $this->callback = function ($algolia, $query, $options) use ($lat, $lng, $radius, $service, $employees_sizes, $callback) {
            $options['aroundLatLng'] = (float) $lat . ',' . (float) $lng;
            
            if(!is_null($radius)) {
                $options['aroundRadius'] = round($radius * 1609.344); // meters
            }
            $filter_string = '(';
            if(!is_null($service)) {
                for($i=1; $i <= 10; $i++) {
                    $filter_string .= 'service'.$i.':"'.$service.'"';
                    if($i !== 10) {
                        $filter_string.=' OR ';
                    }
                }
                $filter_string.= ')';
                
                if(count($employees_sizes) > 0) {
                    $filter_string.= 'OR (';
                    $inc = 0;
                    foreach($employees_sizes as $s) {
                        $filter_string.= 'employees_size_filter:"'.$s.'"';
                        if($inc != count($employees_sizes)-1) {
                            $filter_string.= ' OR ';
                        }
                        $inc++;
                    }
                    $filter_string.= ')';  
                    
                }
                $options['filters'] = $filter_string;
            }
            if ($callback) {
                return call_user_func(
                    $callback,
                    $algolia,
                    $query,
                    $options
                );
            }

            return $algolia->search($query, $options);
        };

        return $this;
    });
}
if (! Builder::hasMacro('with')) {
    /**
     * Override the algolia search options to give you full control over the request,
     * similar to official algolia-laravel package.
     *
     * Adds the final missing piece to scout to make the library useful.
     *
     * @param array $opts Latitude of the center
     *
     * @return Laravel\Scout\Builder
     */
    Builder::macro('with', function ($opts) {
        $callback = $this->callback;

        $this->callback = function ($algolia, $query, $options) use ($opts, $callback) {
            $options = array_merge($options, $opts);

            if ($callback) {
                return call_user_func(
                    $callback,
                    $algolia,
                    $query,
                    $options
                );
            }

            return $algolia->search($query, $options);
        };

        return $this;
    });
}
