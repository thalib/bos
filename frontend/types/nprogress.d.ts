declare module 'nprogress' {
  interface NProgressStatic {
    /**
     * Shows the progress bar and begins trickling progress.
     * @returns {NProgressStatic} The NProgress object.
     */
    start(): NProgressStatic;

    /**
     * Finishes loading by transitioning it to 100%, then fading out.
     * @param {boolean} [force] - If true, it will show the progress bar even if it's hidden.
     * @returns {NProgressStatic} The NProgress object.
     */
    done(force?: boolean): NProgressStatic;

    /**
     * Increments the progress bar with a random amount.
     * @param {number} [amount] - The amount to increment (between 0 and 1).
     * @returns {NProgressStatic} The NProgress object.
     */
    inc(amount?: number): NProgressStatic;

    /**
     * Sets the progress bar status.
     * @param {number} n - The progress percentage (between 0 and 1).
     * @returns {NProgressStatic} The NProgress object.
     */
    set(n: number): NProgressStatic;

    /**
     * Removes the progress indicator.
     */
    remove(): void;

    /**
     * Configuration options for NProgress.
     */
    configure(options: NProgressOptions): NProgressStatic;

    /**
     * Checks if NProgress is currently rendered.
     */
    isRendered(): boolean;

    /**
     * Gets the current progress percentage (between 0 and 1).
     */
    status: number | null;

    /**
     * Gets the NProgress version.
     */
    version: string;
  }

  interface NProgressOptions {
    /**
     * The minimum progress percentage.
     * @default 0.08
     */
    minimum?: number;

    /**
     * How much to increase per trickle.
     * @default 0.02
     */
    trickleSpeed?: number;

    /**
     * Animation speed in milliseconds.
     * @default 200
     */
    speed?: number;

    /**
     * Should spinner be shown?
     * @default true
     */
    showSpinner?: boolean;

    /**
     * The parent container for the progress bar.
     * @default 'body'
     */
    parent?: string;

    /**
     * Should NProgress automatically trickle?
     * @default true
     */
    trickle?: boolean;

    /**
     * CSS easing animation to use.
     * @default 'linear'
     */
    easing?: string;

    /**
     * Template for the progress bar.
     * @default '<div class="bar" role="bar"><div class="peg"></div></div><div class="spinner" role="spinner"><div class="spinner-icon"></div></div>'
     */
    template?: string;
  }

  const nprogress: NProgressStatic;
  export default nprogress;
}
