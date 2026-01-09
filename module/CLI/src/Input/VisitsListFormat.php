<?php

namespace Shlinkio\Shlink\CLI\Input;

enum VisitsListFormat: string
{
    /** Load and dump all visits at once, in a human-friendly format */
    case FULL = 'full';

    /**
     * Load and dump visits in 1000-visit chunks, in a human-friendly format.
     * This format is recommended over `default` for large number of visits, to avoid running out of memory.
     */
    case PAGINATED = 'paginated';

    /** Load and dump visits in chunks, in CSV format */
    case CSV = 'csv';
}
