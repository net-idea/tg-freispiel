import { startStimulusApp } from '@symfony/stimulus-bridge';

// Tell TypeScript about webpack's require.context helper
declare const require: any;

// Registers Stimulus controllers from controllers.json and in the controllers/ directory
const app = startStimulusApp(require.context('@symfony/stimulus-bridge/lazy-controller-loader!./controllers', true, /\.[jt]sx?$/));

export { app };

// register any custom, 3rd party controllers here
// app.register('some_controller_name', SomeImportedController);
