import 'package:logger/logger.dart';

import '../../core/logging/titan_logger.dart';

Logger logger(Type type) => Logger(printer: CustomPrinter(type.toString()));

class CustomPrinter extends LogPrinter {
  CustomPrinter(this.className, {TitanLogRedactor? redactor})
      : _redactor = redactor ?? const TitanLogRedactor();

  final String className;
  final TitanLogRedactor _redactor;

  @override
  List<String> log(LogEvent event) {
    final emoji = PrettyPrinter.defaultLevelEmojis[event.level] ?? '';
    final message = _redactor.redact(event.message);
    return ['$emoji $className : $message'];
  }
}
