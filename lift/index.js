const child_process = require('child_process');

class LiftPlugin {
    constructor(serverless, options) {
        this.serverless = serverless;

        this.setVpc();
        this.setEnvironmentVariables();
        this.setPermissions();
    }

    setVpc() {
        const json = child_process.execSync('lift vpc');
        const details = JSON.parse(json.toString());
        if (details.securityGroupIds && details.subnetIds) {
            this.serverless.service.provider.vpc = details;
        }
    }

    setEnvironmentVariables() {
        this.serverless.service.provider.environment = this.serverless.service.provider.environment || {};

        const json = child_process.execSync('lift variables');
        const variables = JSON.parse(json.toString());

        Object.keys(variables).map(name => {
            if (name in this.serverless.service.provider.environment) {
                // Avoid overwriting an existing variable
                return;
            }
            this.serverless.service.provider.environment[name] = variables[name];
        });
    }

    setPermissions() {
        this.serverless.service.provider.iamRoleStatements = this.serverless.service.provider.iamRoleStatements || [];

        const json = child_process.execSync('lift permissions');
        const permissions = JSON.parse(json.toString());

        this.serverless.service.provider.iamRoleStatements.push(...permissions);
    }
}

module.exports = LiftPlugin;
