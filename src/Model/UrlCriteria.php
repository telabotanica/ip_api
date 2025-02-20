<?php

namespace App\Model;

class UrlCriteria
{
    private ?string $query_parameter;
    private ?string $url_parameter;
    private ?string $bdd_column;
    private ?string $value;
    private ?bool $is_exact;

    /**
     * @param string|null $query_parameter
     * @param string|null $url_parameter
     * @param string|null $bdd_column
     * @param string|null $value
     * @param boolean|null $is_exact
     */
    public function __construct(?string $query_parameter, ?string $url_parameter, ?string $bdd_column, ?string $value, ?bool $is_exact)
    {
        $this->query_parameter = $query_parameter;
        $this->url_parameter = $url_parameter;
        $this->bdd_column = $bdd_column;
        $this->value = $value;
        $this->is_exact = $is_exact;
    }

    /**
     * @return string|null
     */
    public function getQueryParameter(): ?string
    {
        return $this->query_parameter;
    }

    /**
     * @param string|null $query_parameter
     */
    public function setQueryParameter(?string $query_parameter): void
    {
        $this->query_parameter = $query_parameter;
    }

    /**
     * @return string|null
     */
    public function getUrlParameter(): ?string
    {
        return $this->url_parameter;
    }

    /**
     * @param string|null $url_parameter
     */
    public function setUrlParameter(?string $url_parameter): void
    {
        $this->url_parameter = $url_parameter;
    }

    /**
     * @return string|null
     */
    public function getBddColumn(): ?string
    {
        return $this->bdd_column;
    }

    /**
     * @param string|null $bdd_column
     */
    public function setBddColumn(?string $bdd_column): void
    {
        $this->bdd_column = $bdd_column;
    }

    /**
     * @return string|null
     */
    public function getValue(): ?string
    {
        return $this->value;
    }

    /**
     * @param string|null $value
     */
    public function setValue(?string $value): void
    {
        $this->value = $value;
    }

    /**
     * @return bool|null
     */
    public function getIsExact(): ?bool
    {
        return $this->is_exact;
    }

    /**
     * @param bool|null $is_exact
     */
    public function setIsExact(?bool $is_exact): void
    {
        $this->is_exact = $is_exact;
    }


}