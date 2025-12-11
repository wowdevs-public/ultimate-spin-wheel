import React from "react";
import { __ } from "@wordpress/i18n";

class ErrorBoundary extends React.Component {
  constructor(props) {
    super(props);
    this.state = { hasError: false };
  }

  static getDerivedStateFromError(error) {
    return { hasError: true };
  }

  componentDidCatch(error, errorInfo) {
    console.error("ErrorBoundary caught an error:", error, errorInfo);
  }

  render() {
    if (this.state.hasError) {
      return (
        <div className="p-4 text-center bg-red-100 text-red-700 rounded-lg">
          <h2>{__('Something went wrong.', 'ultimate-spin-wheel')}</h2>
          <p>{__('Please try refreshing the page.', 'ultimate-spin-wheel')}</p>
        </div>
      );
    }

    return this.props.children;
  }
}

export default ErrorBoundary;
