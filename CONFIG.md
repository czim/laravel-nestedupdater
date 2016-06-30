# Configuration

## Database Transactions

When a nested model update process is started, it is, by default, run in a database transaction.
This means that if any exception is thrown, all changes will be rolled back. If you do not want
this to happen, unset the `database-transactions` option in the configuration file, or call
`disableDatabaseTransactions()` on the model updater before running the process.


## Relations Configuration

