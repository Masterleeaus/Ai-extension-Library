import 'package:flutter/material.dart';

enum TitanTextFieldVariant { boxed, basic }

class TitanTextField extends StatefulWidget {
  const TitanTextField({
    required this.controller,
    required this.label,
    this.hint,
    this.helperText,
    this.errorText,
    this.variant = TitanTextFieldVariant.boxed,
    this.prefix,
    this.suffix,
    this.showClear = false,
    this.obscureText = false,
    this.maxLines = 1,
    this.keyboardType,
    this.textInputAction,
    this.enabled = true,
    this.onChanged,
    super.key,
  });

  final TextEditingController controller;
  final String label;
  final String? hint;
  final String? helperText;
  final String? errorText;
  final TitanTextFieldVariant variant;
  final Widget? prefix;
  final Widget? suffix;
  final bool showClear;
  final bool obscureText;
  final int maxLines;
  final TextInputType? keyboardType;
  final TextInputAction? textInputAction;
  final bool enabled;
  final ValueChanged<String>? onChanged;

  @override
  State<TitanTextField> createState() => _TitanTextFieldState();
}

class _TitanTextFieldState extends State<TitanTextField> {
  late bool _isObscured;

  @override
  void initState() {
    super.initState();
    _isObscured = widget.obscureText;
    widget.controller.addListener(_handleControllerChanged);
  }

  @override
  void didUpdateWidget(covariant TitanTextField oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (oldWidget.controller != widget.controller) {
      oldWidget.controller.removeListener(_handleControllerChanged);
      widget.controller.addListener(_handleControllerChanged);
    }
    if (oldWidget.obscureText != widget.obscureText) {
      _isObscured = widget.obscureText;
    }
  }

  @override
  void dispose() {
    widget.controller.removeListener(_handleControllerChanged);
    super.dispose();
  }

  void _handleControllerChanged() {
    if (mounted) setState(() {});
  }

  @override
  Widget build(BuildContext context) {
    final suffixes = <Widget>[];
    if (widget.showClear && widget.controller.text.isNotEmpty) {
      suffixes.add(
        IconButton(
          tooltip: 'Clear ${widget.label}',
          onPressed: widget.enabled ? widget.controller.clear : null,
          icon: const Icon(Icons.cancel_outlined),
        ),
      );
    }
    if (widget.obscureText) {
      suffixes.add(
        IconButton(
          tooltip: '${_isObscured ? 'Show' : 'Hide'} ${widget.label}',
          onPressed: widget.enabled
              ? () => setState(() => _isObscured = !_isObscured)
              : null,
          icon: Icon(
            _isObscured ? Icons.visibility_outlined : Icons.visibility_off_outlined,
          ),
        ),
      );
    }
    if (widget.suffix != null) suffixes.add(widget.suffix!);

    final border = widget.variant == TitanTextFieldVariant.basic
        ? const UnderlineInputBorder()
        : const OutlineInputBorder();

    return TextField(
      controller: widget.controller,
      enabled: widget.enabled,
      obscureText: _isObscured,
      maxLines: widget.obscureText ? 1 : widget.maxLines,
      keyboardType: widget.keyboardType,
      textInputAction: widget.textInputAction,
      onChanged: widget.onChanged,
      decoration: InputDecoration(
        labelText: widget.label,
        hintText: widget.hint,
        helperText: widget.helperText,
        errorText: widget.errorText,
        prefixIcon: widget.prefix,
        suffixIcon: suffixes.isEmpty
            ? null
            : Row(mainAxisSize: MainAxisSize.min, children: suffixes),
        border: border,
        enabledBorder: border,
      ),
    );
  }
}
