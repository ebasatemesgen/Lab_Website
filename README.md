## Introduction
This guide provides step-by-step instructions for setting up a local Drupal environment using ddev and Docker, following the [University of Minnesota's guide](https://it.umn.edu/services-technologies/how-tos/drupal-enterprise-set-local-environment). Additionally, it covers setting up three themes: a custom theme (Ebasa's Theme), Vani, and Fortwell.

## Prerequisites
- Docker installed on your machine
- ddev installed on your machine
- Git installed on your machine
- Basic knowledge of command line operations

## Setup Instructions

### Step 1: Clone the Repository
Clone the repository containing the Drupal site setup. Replace `<repository-url>` with your repository URL.
```sh
git clone <repository-url>
cd <repository-directory>
```

### Step 2: Install ddev
Install ddev by following the instructions on the [official ddev website](https://ddev.readthedocs.io/en/stable/#installation).

### Step 3: Configure ddev
Run the following command to configure ddev in your project directory:
```sh
ddev config
```
Follow the prompts to set up the project name and type (`drupal8` or `drupal9` depending on your Drupal version).

### Step 4: Start ddev
Start the ddev environment by running:
```sh
ddev start
```

### Step 5: Import the Database
Import the database if you have a SQL file. Replace `<path-to-sql-file>` with the path to your SQL file.
```sh
ddev import-db --src=<path-to-sql-file>
```

### Step 6: Import Files
Import files if you have a tarball of your files directory. Replace `<path-to-files-archive>` with the path to your files archive.
```sh
ddev import-files --src=<path-to-files-archive>
```

### Step 7: Access the Local Site
Access your local Drupal site by navigating to:
```sh
ddev launch
```

## Theme Setup

### Ebasa's Custom Theme
Follow the instructions in the [YouTube tutorial](https://www.youtube.com/watch?v=XOV8VNTAvek&list=PLUBR53Dw-Ef818EUxzNoWKcQ7PYUXpFFA) to set up the custom theme.

### Vani Theme
1. Download the Vani theme from your preferred source or the Drupal theme repository.
2. Place the theme in the `/themes/custom` directory of your Drupal installation.
3. Enable the theme:
    ```sh
    ddev drush en vani
    ```
4. Set it as the default theme:
    ```sh
    ddev drush config-set system.theme default vani
    ```

### Fortwell Theme
1. Download the Fortwell theme from your preferred source or the Drupal theme repository.
2. Place the theme in the `/themes/custom` directory of your Drupal installation.
3. Enable the theme:
    ```sh
    ddev drush en fortwell
    ```
4. Set it as the default theme:
    ```sh
    ddev drush config-set system.theme default fortwell
    ```

## Additional Configuration

### Configuration Management
To import configuration changes, use:
```sh
ddev drush cim
```

To export configuration changes, use:
```sh
ddev drush cex
```

### Clear Cache
To clear the Drupal cache, use:
```sh
ddev drush cr
```

## Troubleshooting
- If you encounter issues, refer to the [ddev documentation](https://ddev.readthedocs.io/en/stable/#troubleshooting) for common troubleshooting steps.
- Ensure Docker is running and that your system meets the necessary requirements.
- Check file and directory permissions if you encounter permission-related errors.

## Conclusion
By following these instructions, you should have a fully functional local Drupal environment with your custom themes set up. For further customization and development, refer to the Drupal and ddev documentation.

