<?php

/**
 * This file is a part of horstoeko/zugferdublbridge.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace horstoeko\zugferdublbridge\traits;

/**
 * Trait for handling supported profiles
 *
 * @category Zugferd-UBL-Bridge
 * @package  Zugferd-UBL-Bridge
 * @author   D. Erling <horstoeko@erling.com.de>
 * @license  https://opensource.org/licenses/MIT MIT
 * @link     https://github.com/horstoeko/zugferdublbridge
 */
trait HandlesProfiles
{
    /**
     * Internal Flag to force a profile in destination
     *
     * @var string
     */
    private $forceDestinationProfile = "";

    /**
     * Returns a list of supported profiles
     *
     * @return string[]
     */
    protected function getSupportedProfiles(): array
    {
        return [
            'urn:factur-x.eu:1p0:minimum',
            'urn:factur-x.eu:1p0:basicwl',
            'urn:cen.eu:en16931:2017#compliant#urn:factur-x.eu:1p0:basic',
            'urn:cen.eu:en16931:2017',
            'urn:cen.eu:en16931:2017#conformant#urn:factur-x.eu:1p0:extended',
            'urn:cen.eu:en16931:2017#compliant#urn:xoev-de:kosit:standard:xrechnung_1.2',
            'urn:cen.eu:en16931:2017#compliant#urn:xoev-de:kosit:standard:xrechnung_2.0',
            'urn:cen.eu:en16931:2017#compliant#urn:xoev-de:kosit:standard:xrechnung_2.1',
            'urn:cen.eu:en16931:2017#compliant#urn:xoev-de:kosit:standard:xrechnung_2.2',
            'urn:cen.eu:en16931:2017#compliant#urn:xoev-de:kosit:standard:xrechnung_2.3',
            'urn:cen.eu:en16931:2017#compliant#urn:xeinkauf.de:kosit:xrechnung_3.0',
        ];
    }

    /**
     * Returns true if the profiles $profileToCheck is a supported profile
     *
     * @param  string $profileToCheck
     * @return boolean
     */
    public function isSupportedProfile(string $profileToCheck): bool
    {
        return in_array($profileToCheck, $this->getSupportedProfiles());
    }

    /**
     * Returns the profile code to force
     *
     * @return string
     */
    public function getForceDestinationProfile(): string
    {
        return $this->forceDestinationProfile;
    }

    /**
     * Returns the profile code to force. If no profile to force is given the
     * $default value will be returned
     *
     * @param string $defaultProfile
     * @return string
     */
    public function getForceDestinationProfileWithDefault(string $defaultProfile): string
    {
        if ($this->getForceDestinationProfile()) {
            return $this->getForceDestinationProfile();
        }

        return $defaultProfile;
    }

    /**
     * Set the profile to force in the destination (UBL) document
     *
     * @param  string $forceDestinationProfile
     * @return static
     */
    public function setForceDestinationProfile(string $forceDestinationProfile)
    {
        if (!in_array($forceDestinationProfile, $this->getSupportedProfiles())) {
            return $this;
        }

        $this->forceDestinationProfile = $forceDestinationProfile;

        return $this;
    }

    /**
     * Unsert the profile to force in the destination (UBL) document
     *
     * @return static
     */
    public function clearForceDestinationProfile()
    {
        $this->forceDestinationProfile = "";

        return $this;
    }

    /**
     * Force the MINIMUM profile
     *
     * @return static
     */
    public function setForceDestinationProfileMinimum()
    {
        $this->setForceDestinationProfile('urn:factur-x.eu:1p0:minimum');

        return $this;
    }

    /**
     * Force the BASICWL profile
     *
     * @return static
     */
    public function setForceDestinationProfileBasicWL()
    {
        $this->setForceDestinationProfile('urn:factur-x.eu:1p0:basicwl');

        return $this;
    }

    /**
     * Force the BASIC profile
     *
     * @return static
     */
    public function setForceDestinationProfileBasic()
    {
        $this->setForceDestinationProfile('urn:cen.eu:en16931:2017#compliant#urn:factur-x.eu:1p0:basic');

        return $this;
    }

    /**
     * Force the COMFORT (EN16931) profile
     *
     * @return static
     */
    public function setForceDestinationProfileEn16931()
    {
        $this->setForceDestinationProfile('urn:cen.eu:en16931:2017');

        return $this;
    }

    /**
     * Force the EXTENDED profile
     *
     * @return static
     */
    public function setForceDestinationProfileExtended()
    {
        $this->setForceDestinationProfile('urn:cen.eu:en16931:2017#conformant#urn:factur-x.eu:1p0:extended');

        return $this;
    }

    /**
     * Force the XRECHNUNG 3.0 profile
     *
     * @return static
     */
    public function setForceDestinationProfileXRechnung30()
    {
        $this->setForceDestinationProfile('urn:cen.eu:en16931:2017#compliant#urn:xeinkauf.de:kosit:xrechnung_3.0');

        return $this;
    }
}
