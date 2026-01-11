import { startStimulusApp } from '@symfony/stimulus-bridge';

// Allow webpack's require.context in TS
// eslint-disable-next-line @typescript-eslint/no-explicit-any
declare const require: any;

// Registers Stimulus controllers from controllers.json and in the controllers/ directory
export const app = startStimulusApp(require.context('@symfony/stimulus-bridge/lazy-controller-loader!./controllers', true, /\.tsx?$/));
