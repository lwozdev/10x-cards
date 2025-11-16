import { startStimulusApp } from '@symfony/stimulus-bundle';
import GenerateController from './controllers/generate_controller.js';
import EditSetController from './controllers/edit_set_controller.js';
import HelloController from './controllers/hello_controller.js';

const app = startStimulusApp();

// Register custom controllers
app.register('generate', GenerateController);
app.register('edit-set', EditSetController);
app.register('hello', HelloController);
