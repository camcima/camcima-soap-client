<?php

namespace Camcima\Soap\Test\Fixtures;

/**
 * ForecastEntry Class Firxture
 *
 * @author Carlos Cima
 */
class ForecastEntry
{
    public $Date;
    public $WeatherID;
    public $Desciption;
    public $Temperatures;
    public $ProbabilityOfPrecipiation;

    public function setDate(\DateTime $Date)
    {
        $this->Date = $Date;
        return $this;
    }
}
