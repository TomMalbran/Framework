<?php
namespace Framework\Utils;

/**
 * The Search Results
 */
class SearchResults {

    public string $text   = "";
    public int    $amount = 50;

    /** @var array{}[] */
    public array  $list   = [];


    /**
     * Creates a new SearchResults instance
     * @param string  $text   Optional.
     * @param integer $amount Optional.
     */
    public function __construct(string $text = "", int $amount = 50) {
        $this->text   = $text;
        $this->amount = $amount;
    }



    /**
     * Returns true if it can add more Results
     * @return boolean
     */
    public function canAdd(): bool {
        return $this->amount > 0;
    }

    /**
     * Adds the given Results
     * @param array{}[] $results
     * @param string    $type
     * @param string    $url
     * @param string    $name
     * @return integer
     */
    public function add(array $results, string $type, string $url, string $name): int {
        foreach ($results as $result) {
            $this->list[] = [
                "type"  => $type,
                "url"   => $url,
                "name"  => $name,
                "id"    => $result["id"],
                "title" => $result["title"],
            ];
            $this->amount -= 1;
        }
        return $this->amount;
    }
}
