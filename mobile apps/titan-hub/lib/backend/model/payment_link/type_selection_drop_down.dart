import '../../../widgets/payment_link/custom_drop_down.dart';

class TypeSelectionModel implements DropdownModel {
  final String label;

  TypeSelectionModel(this.label);

  @override
  String get title => label;
}
