<?php

class TwoSpaces
{
  public string $name {
    get {
      return strtoupper($this->name);
    }
  }

  public function run(int $count): int
  {
    if ($count > 0) {
      return $count;
    }

    return 0;
  }
}
