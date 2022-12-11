<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ReworkFile extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rework:file {file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rework a given file';

    /**
     * @var array
     */
    protected array $emails;

    /**
     * @var array
     */
    protected array $filters;

    public function __construct()
    {
        parent::__construct();

        $this->emails = json_decode(
            file_get_contents(storage_path('mapping/emails.json'), true)
        );

        $this->filters = json_decode(
            file_get_contents(storage_path('mapping/filters.json'), true)
        );
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        if(!$this->confirm("Are you sure you want to rework the file '{$this->argument('file')}' ?"))
        {
            $this->error('Rework was aborted');

            return Command::SUCCESS;
        }

        $this->info('Processing...');

        $results = [];
        foreach ($this->transformedUsersArray() as $user) {
            $results[] = $this->buildDataForUser($user);
        }

        file_put_contents(
            public_path('users.json'),
            json_encode($results)
        );

        $this->info('The file rework was successful!');

        return Command::SUCCESS;
    }

    /**
     * Convert the given csv file to multidimensional array
     *
     * @return array
     */
    private function convertCsvToArray(): array
    {
        $usersArray = [];

        if ($open = fopen($this->argument('file'), 'r')) {
            while (($data = fgetcsv($open, null, ';')) !== false) {
                $usersArray[] = $data;
            }

            fclose($open);
        }

        return $usersArray;
    }

    /**
     * Adds the column headers as keys for the users' values
     *
     * @return array
     */
    private function transformedUsersArray(): array
    {
        $usersArray = $this->convertCsvToArray();
        $columnNames = $usersArray[0];
        unset($usersArray[0]);

        $transformedUsersArray = [];
        foreach ($usersArray as $userValues) {
            $transformedUsersArray[] = array_combine($columnNames, $userValues);
        }

        return $transformedUsersArray;
    }

    /**
     * Get the index for a given email address
     *
     * @param string $email
     * @return int|string|null
     */
    private function getEmailIndex(string $email): int|string|null
    {
        foreach ($this->emails as $index => $value) {
            if ($value->email === $email) {
                return $index;
            }
        }

        return null;
    }

    /**
     * Get the index for a filter
     *
     * @param string $filter
     * @return int|string|null
     */
    private function getFilterIndex(string $filter): int|string|null
    {
        foreach ($this->filters as $index => $val) {
            if ($val->name->en === $filter) {
                return $index;
            }
        }

        return null;
    }

    /**
     * Replace the user's values with the corresponding ids
     *
     * @param array $userData
     * @return array
     */
    private function buildDataForUser(array $userData): array
    {
        $newUserdata = [];

        foreach ($userData as $key => $value) {
            if ($key === 'email') {
                $foundEmailsIndex = $this->getEmailIndex($value);

                if (!is_null($foundEmailsIndex)) {
                    $newUserdata['_id'] = $this->emails[$foundEmailsIndex]->_id;
                }
            } else {
                $foundFiltersIndex = $this->getFilterIndex($key);

                if (!is_null($foundFiltersIndex)) {
                    $filterValues = $this->filters[$foundFiltersIndex]->values;
                    $filterValue = array_filter($filterValues, fn($filterValue) => $filterValue->en === $value);
                    $filterValue = array_shift($filterValue);

                    if (is_object($filterValue)) {
                        $newUserdata['attributes'][] = $filterValue->_id;
                    }
                }
            }
        }

        $this->info("Rework done for user with email: {$userData['email']}");

        return $newUserdata;
    }
}
