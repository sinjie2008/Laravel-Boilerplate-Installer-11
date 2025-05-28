<?php

namespace Installer;

class Util
{
    public function rrmdir(string $dir): void
    {
        if (is_dir($dir)) {
            $objects = scandir($dir);
            if ($objects !== false) {
                foreach ($objects as $object) {
                    if ($object != "." && $object != "..") {
                        if (is_dir($dir . DIRECTORY_SEPARATOR . $object) && !is_link($dir . DIRECTORY_SEPARATOR . $object)) {
                            $this->rrmdir($dir . DIRECTORY_SEPARATOR . $object);
                        } else {
                            @unlink($dir . DIRECTORY_SEPARATOR . $object);
                        }
                    }
                }
            }
            @rmdir($dir);
        }
    }

    public function returnBytes(string $val): int
    {
        $val = trim($val);
        if (empty($val)) {
            return 0;
        }

        $last = strtolower($val[strlen($val) - 1]);
        $valNum = (int)$val;

        switch ($last) {
            case 'g': $valNum *= 1024; // Fall-through
            case 'm': $valNum *= 1024; // Fall-through
            case 'k': $valNum *= 1024;
        }

        return $valNum;
    }
}
