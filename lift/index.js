const Lift = require('@mnapoli/lift');

class LiftPlugin {
    constructor(serverless, options) {
        this.serverless = serverless;
        this.lift = Lift;

        this.setVpc()
            .then(() => {
                return this.setEnvironmentVariables();
            })
            .then(() => {
                return this.setPermissions();
            });
    }

    async setVpc() {
        const details = await this.lift.Vpc.getOutput();
        if (details.securityGroupIds && details.subnetIds) {
            this.serverless.service.provider.vpc = details;
        }
    }

    async setEnvironmentVariables() {
        this.serverless.service.provider.environment = this.serverless.service.provider.environment || {};

        const variables = await this.lift.Variables.getOutput();

        Object.keys(variables).map(name => {
            if (name in this.serverless.service.provider.environment) {
                // Avoid overwriting an existing variable
                return;
            }
            this.serverless.service.provider.environment[name] = variables[name];
        });
    }

    async setPermissions() {
        this.serverless.service.provider.iamRoleStatements = this.serverless.service.provider.iamRoleStatements || [];

        const permissions = await this.lift.Permissions.getOutput();

        this.serverless.service.provider.iamRoleStatements.push(...permissions);
    }
}

module.exports = LiftPlugin;
